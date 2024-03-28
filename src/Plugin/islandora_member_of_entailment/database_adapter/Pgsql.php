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
  public function schema(): void {
    $this->uninstallSchema();
    // XXX: hook_schema() is unable to deal with array-valued columns.
    // XXX: jsonb doesn't quite work instead of arrays, due to
    // expecting/requiring text, not apparently supporting the list of integers.
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
    )->execute();
  }

  /**
   * {@inheritDoc}
   */
  public function uninstallSchema(): void {
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
    )->execute();
  }

  /**
   * {@inheritDoc}
   */
  public function rebuild(): void {
    $this->connection->query(
      <<<EOQ
TRUNCATE {{$this->getTableName()}};
WITH RECURSIVE ancestors(nid, ancestor, path, is_cycle) AS (
  SELECT n.nid::bigint, NULL::bigint, ARRAY[n.nid::bigint]::bigint[], false::boolean
  FROM node n
  WHERE NOT EXISTS ( SELECT 1 FROM node__field_member_of fmo WHERE n.nid = fmo.entity_id )
    AND n.type = 'islandora_object'
UNION ALL
  SELECT fmou.entity_id, a.nid, a.path || fmou.entity_id, fmou.entity_id = ANY(a.path)
  FROM ancestors a, node__field_member_of fmou
  WHERE fmou.field_member_of_target_id = a.nid
    AND NOT a.is_cycle
)
INSERT INTO {{$this->getTableName()}} SELECT nid, ancestor, path FROM ancestors
EOQ,
      options: [
        'allow_delimiter_in_query' => TRUE,
        'allow_square_brackets' => TRUE,
      ],
    )->execute();
  }

  /**
   * {@inheritDoc}
   */
  public function addNode(NodeInterface $node): void {
    $transaction = $this->connection->startTransaction();
    try {
      $this->connection->query(<<<EOQ
WITH derived(nid::bigint, ancestor::bigint, path::bigint[], is_cycle::bool) AS (
  SELECT :current, a.ancestor, a.path || :current,  :current = ANY(a.path)
  FROM {{$this->getTableName()}}
  WHERE a.ancestor IN (:parents[])
)
INSERT INTO {$this->getTableName()} SELECT nid, ancestor, path FROM derived
EOQ,
        [
          ':current' => $node->id(),
          ':parents[]' => $this->getParents($node),
        ],
        [
          'allow_square_brackets' => TRUE,
        ],
      )->execute();
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function updateNode(NodeInterface $node): void {
    [$deleted_parents, $new_parents] = $this->getChangedParents($node);

    // If the relationship did not change, return.
    if (empty($new_parents) && empty($deleted_parents)) {
      return;
    }

    $transaction = $this->connection->startTransaction();
    try {
      // @todo Add entries for nodes that are newly related (attempt to make use
      // of present LUT to update?).
      // @todo Delete entries for nodes that are no longer related.
      // At worst, we can removed everything and add it anew.
      if ($deleted_parents) {
        $this->connection->query(<<<EOQ
DELETE FROM {{$this->getTableName()}}
WHERE :current IN path AND aid IN (:parents[]);
EOQ,
          [
            ':current' => $node->id(),
            ':parents[]' => $deleted_parents,
          ],
        )->execute();
      }
      if ($new_parents) {
        $this->connection->query(
          <<<EOQ
WITH RECURSIVE tree(nid::bigint, ancestor::bigint, path::bigint[] , is_cycle::bool) AS (
    SELECT :current, a.aid, a.path || :current, false
    FROM {{$this->getTableName()}} a
    WHERE a.aid in (:parents[]) AND NOT (:current = ANY(a.path))
  UNION ALL
    SELECT fmo.entity_id, t.ancestor, t.path || fmo.entity_id, fmo.entity_id = ANY(t.path)
    FROM tree t
    INNER JOIN node__field_member_of fmo ON fmo.field_member_of_target_id = t.nid
    WHERE NOT is_cycle
)
INSERT INTO {{$this->getTableName()}} SELECT nid, ancestor, path FROM tree;
EOQ,
          [
            ':current' => $node->id(),
            ':parents[]' => $new_parents,
          ],
          [
            'allow_square_brackets' => TRUE,
          ],
        )->execute();
      }
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function deleteNode(NodeInterface $node): void {
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
      )->execute();
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    }
  }

}
