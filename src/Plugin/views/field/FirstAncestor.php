<?php

namespace Drupal\islandora_member_of_entailment\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to display the first ancestor of a node.
 *
 * @ViewsField("imoe_first_ancestor")
 */
class FirstAncestor extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['render_type'] = [
      'default' => 'id',
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['render_type'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => [
        'id' => $this->t('First ancestor node ID'),
        'label' => $this->t('First ancestor node label'),
        'both' => $this->t('First ancestor node ID and label'),
      ],
      '#title' => $this->t('Render format'),
      '#default_value' => $this->options['render_type'],
      '#description' => $this->t('Choose the render format.'),
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $nid = $this->getValueFromRelationship($values);

    // Return the top-level ancestor ID.
    if ($nid) {
      return $this->getTopLevelAncestor($nid);
    }

    return NULL;
  }

  /**
   * Gets the top-level ancestor of a node.
   *
   * @param int $nid
   *   The node ID.
   *
   * @return \Drupal\Component\Render\FormattableMarkup|null
   *   The top-level ancestor ID, or NULL if no ancestor.
   */
  private function getTopLevelAncestor(int $nid): ?string {
    // Query to fetch the paths for the given nid.
    $query = \Drupal::database()->select('islandora_member_of_entailment', 'i')
      ->fields('i', ['path'])
      ->condition('nid', $nid);

    // Execute the query and fetch the results.
    $result = $query->execute()->fetchCol();

    // Initialize variables to track the longest path.
    $ancestorPath = [];

    // Iterate through the fetched paths.
    foreach ($result as $path) {
      // Clean up the path format to make it valid JSON.
      // Remove curly braces.
      $path = trim($path, '{}');
      // Split by commas.
      $path_values = explode(',', $path);

      // Check if this path is longer than the currently tracked longest path.
      if (count($path_values) > count($ancestorPath)) {
        $ancestorPath = $path_values;
      }
    }

    if (!empty($ancestorPath)) {
      $ancestorId = end($ancestorPath);

      if ($this->options['render_type'] === 'id') {
        return $ancestorId;
      }

      // Load the ancestor node.
      $ancestor = Node::load($ancestorId);

      if ($this->options['render_type'] === 'label') {
        return $ancestor instanceof NodeInterface ? $ancestor->label() : NULL;
      }

      if ($this->options['render_type'] === 'both') {
        return $ancestor instanceof NodeInterface ? $ancestor->label() . " ({$ancestorId})" : NULL;
      }
    }

    return NULL;
  }

  /**
   * Gets the nid value from field media of.
   *
   * @param \Drupal\views\ResultRow $values
   *   The values.
   *
   * @return int|null
   *   The node ID, or NULL if no node.
   */
  protected function getValueFromRelationship(ResultRow $values): ?int {
    if ($parentNode = $values->_relationship_entities['field_media_of']) {
      return $parentNode->id();
    }

    return NULL;
  }

}
