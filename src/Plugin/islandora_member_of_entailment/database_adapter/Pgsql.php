<?php

namespace Drupal\islandora_member_of_entailment\Plugin\islandora_member_of_entailment\database_adapter;

use Drupal\islandora_member_of_entailment\Plugin\DatabaseAdapterPluginBase;
use Drupal\node\NodeInterface;

/**
 * PostgreSQL adapter.
 *
 * @DatabaseAdapter(
 *   id = "pgsql"
 * )
 */
class Pgsql extends DatabaseAdapterPluginBase {

  /**
   * {@inheritDoc}
   */
  public function schema(): bool {
    $this->uninstallSchema();
    // XXX: hook_schema() is unable to deal with array-valued columns.
    // XXX: jsonb doesn't quite work instead of arrays, due to
    // expecting/requiring text, not apparently supporting the list of
    // integers.
    $this->connection->query(
      <<<EOQ
CREATE TABLE IF NOT EXISTS {{$this->getTableName()}} (
  nid bigint,
  aid bigint,
  path bigint[]
);
CREATE INDEX IF NOT EXISTS {{$this->getTableName()}_nid_idx} ON {{$this->getTableName()}} (nid);
CREATE INDEX IF NOT EXISTS {{$this->getTableName()}_idx} ON {{$this->getTableName()}} (aid);
CREATE INDEX IF NOT EXISTS {{$this->getTableName()}_path_idx} ON {{$this->getTableName()}} USING GIN (path array_ops);
EOQ,
      options: [
        'allow_delimiter_in_query' => TRUE,
        'allow_square_brackets' => TRUE,
      ],
    );
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function uninstallSchema(): bool {
    $this->connection->query(
      <<<EOQ
DROP INDEX IF EXISTS {{$this->getTableName()}_nid_idx};
DROP INDEX IF EXISTS {{$this->getTableName()}_aid_idx};
DROP INDEX IF EXISTS {{$this->getTableName()}_path_idx};
DROP TABLE IF EXISTS {{$this->getTableName()}};
EOQ,
      options: [
        'allow_delimiter_in_query' => TRUE,
      ]
    );
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function rebuild(): bool {
    $this->connection->truncate($this->getTableName())->execute();
    $this->connection
      ->query(
        <<<EOQ
WITH RECURSIVE ancestors(nid, ancestor, path, is_cycle) AS (
    SELECT fmo.entity_id::bigint, fmo.field_member_of_target_id::bigint, ARRAY[fmo.field_member_of_target_id::bigint]::bigint[], (fmo.entity_id = fmo.field_member_of_target_id)::boolean
    FROM {node__field_member_of} fmo
  UNION ALL
    SELECT l.nid, fmou.field_member_of_target_id, l.path || fmou.field_member_of_target_id, fmou.field_member_of_target_id = ANY(l.path)
    FROM ancestors l, {node__field_member_of} fmou
    WHERE l.ancestor = fmou.entity_id
      AND NOT l.is_cycle
)
INSERT INTO {{$this->getTableName()}} SELECT nid, ancestor, path FROM ancestors
EOQ,
        options: [
          'allow_square_brackets' => TRUE,
        ],
      );

    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function addNode(NodeInterface $node): bool {
    $transaction = $this->connection->startTransaction();
    try {
      $this->connection->query(<<<EOQ
WITH current(nid, ancestor, path) AS (
  SELECT fmo.entity_id, fmo.field_member_of_target_id, ARRAY[fmo.field_member_of_target_id]
  FROM {node__field_member_of} fmo
  WHERE fmo.entity_id = :current
), tree_above(nid, ancestor, path) AS (
  SELECT fmo.entity_id, a.aid, ARRAY[fmo.field_member_of_target_id] || a.path
  FROM {{$this->getTableName()}} a, {node__field_member_of} fmo
  WHERE fmo.field_member_of_target_id = a.nid
    AND fmo.entity_id = :current
    AND NOT fmo.entity_id = ANY(a.path)
), derived(nid, ancestor, path) AS (
    SELECT c.nid, c.ancestor, c.path
    FROM current c
  UNION ALL
    SELECT a.nid, a.ancestor, a.path
    FROM tree_above a
)
INSERT INTO {{$this->getTableName()}} SELECT nid, ancestor, path FROM derived
EOQ,
        [
          ':current' => $node->id(),
        ],
        [
          'allow_square_brackets' => TRUE,
        ],
      );
      return TRUE;
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function updateNode(NodeInterface $node): bool {
    [$deleted_parents, $new_parents] = $this->getChangedParents($node);

    // If the relationship did not change, return.
    if (empty($new_parents) && empty($deleted_parents)) {
      return TRUE;
    }

    $transaction = $this->connection->startTransaction();
    try {
      // @todo Add entries for nodes that are newly related (attempt to make use
      // of present LUT to update?).
      // @todo Delete entries for nodes that are no longer related.
      // At worst, we can remove everything and add it anew.
      if ($deleted_parents) {
        $this->connection->query(<<<EOQ
DELETE FROM {{$this->getTableName()}}
WHERE :current = ANY(path) AND aid IN (:parents[]);
EOQ,
          [
            ':current' => $node->id(),
            ':parents[]' => $deleted_parents,
          ],
        );
      }

      if ($new_parents) {
        // A number of relationships to account for:
        // - Those directly described (the new parent(s) for the given node).
        // - We have all the same ancestors as our parents.
        // - All of our children get the same ancestors as our parents.
        $this->connection->query(
          <<<EOQ
WITH tree_given(nid, ancestor, path) AS (
    SELECT CAST( :current AS bigint ), n.nid, ARRAY[n.nid]
    FROM {node} n
    WHERE n.nid IN ( :parents[] )
), tree_above(nid, ancestor, path , is_cycle) AS (
    SELECT CAST( :current AS bigint ), a.aid, ARRAY[CAST( :current AS bigint )] || a.path, CAST( :current AS bigint ) = ANY(a.path)
    FROM {{$this->getTableName()}} a
    WHERE a.nid in ( :parents[] )
), tree_below(nid, ancestor, path, is_cycle) AS (
    SELECT d.nid, CAST( :current AS bigint ), d.path || CAST( :current AS bigint ), CAST( :current AS bigint ) = ANY(d.path)
    FROM {{$this->getTableName()}} d
    WHERE d.aid = :current
), tree_splice(nid, ancestor, path, is_cycle) AS (
    SELECT b.nid, a.ancestor, b.path || a.path, a.path && b.path
    FROM tree_below b, tree_above a
    WHERE b.ancestor = a.nid
), tree_union(nid, ancestor, path) AS (
    SELECT g.nid, g.ancestor, g.path
    FROM tree_given g
  UNION ALL
    SELECT a.nid, a.ancestor, a.path
    FROM tree_above a
    WHERE NOT a.is_cycle
  UNION ALL
    SELECT b.nid, b.ancestor, b.path
    FROM tree_below b
    WHERE NOT b.is_cycle
  UNION ALL
    SELECT s.nid, s.ancestor, s.path
    FROM tree_splice s
    WHERE NOT s.is_cycle
)
INSERT INTO {{$this->getTableName()}} SELECT nid, ancestor, path FROM tree_union
EOQ,
          [
            ':current' => $node->id(),
            ':parents[]' => $new_parents,
          ],
          options: [
            'allow_square_brackets' => TRUE,
          ],
        );
      }

      return TRUE;
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function deleteNode(NodeInterface $node): bool {
    $transaction = $this->connection->startTransaction();
    try {
      $this->connection->query(
        <<<EOQ
DELETE FROM {{$this->getTableName()}}
WHERE nid = :current
OR aid = :current
OR ARRAY[:current]::bigint[] && path
EOQ,
        [
          ':current' => $node->id(),
        ],
        [
          'allow_square_brackets' => TRUE,
        ],
      );
      return TRUE;
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    }
  }

}
