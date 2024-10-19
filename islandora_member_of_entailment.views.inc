<?php

/**
 * @file
 * Views hooks.
 */

/**
 * Implements hook_views_data().
 */
function islandora_member_of_entailment_views_data() : array {
  $data = [];

  $data['islandora_member_of_entailment'] = [
    'table' => [
      'group' => \t('Islandora Member Of Entailment'),
      'provider' => 'islandora_member_of_entailment',
    ],
    'nid' => [
      'title' => \t('Described node'),
      'relationship' => [
        'base' => 'node_field_data',
        'base field' => 'nid',
        'id' => 'standard',
        'label' => \t('Described node'),
      ],
    ],
    'aid' => [
      'title' => \t('Ancestor node'),
      'relationship' => [
        'base' => 'node_field_data',
        'base field' => 'nid',
        'id' => 'standard',
        'label' => \t('Ancestor node'),
      ],
    ],
    // Contextual filter for filtering to children of a given container object.
    'is_descendant_of' => [
      'title' => t('IMoE: Is descendant of'),
      'help' => t('Limit to descendants of a given Islandora entity'),
      'argument' => [
        'id' => 'islandora_member_of_entailment_argument_is_descendant_of_entity',
      ],
    ],
  ];

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function islandora_member_of_entailment_views_data_alter(array &$data) {
  $data['node_field_data']['islandora_member_of_entailment_node'] = [
    'title' => \t('Islandora Member Of Entailment: Node'),
    'relationship' => [
      'base' => 'islandora_member_of_entailment',
      'base field' => 'nid',
      'field' => 'nid',
      'id' => 'standard',
      'label' => \t('IMoE: Node'),
    ],
  ];
  $data['node_field_data']['islandora_member_of_entailment_ancestor'] = [
    'title' => \t('Islandora Member Of Entailment: Ancestor'),
    'relationship' => [
      'base' => 'islandora_member_of_entailment',
      'base field' => 'aid',
      'field' => 'nid',
      'id' => 'standard',
      'label' => \t('IMoE: Ancestor'),
    ],
  ];
}
