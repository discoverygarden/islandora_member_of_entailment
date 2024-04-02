<?php

namespace Drupal\Tests\islandora_member_of_entailment\Kernel;

use Drupal\islandora\IslandoraUtils;

/**
 * Test LUT maintance/building with updates.
 */
class TableUpdateTest extends AbstractBase {

  use DiamondBuilderTrait;

  /**
   * Test adding a new root element.
   *
   * @dataProvider buildType
   */
  public function testUpdateRoot(bool $regenerate, bool $saving) {
    $diamond = $this->buildDiamond($regenerate, $saving);
    $alpha = $diamond[0];

    $map = $this->getDiamondMap($diamond);

    $new_root = $this->createNode();
    $new_root->save();

    $alpha->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($new_root);
    $alpha->save();

    $new_map = $map;
    $new_map[] = [
      'nid' => $alpha->id(),
      'aid' => $new_root->id(),
    ];
    foreach ($map as $item) {
      if ($item['aid'] == $alpha->id()) {
        $new_map[] = [
          'nid' => $item['nid'],
          'aid' => $new_root->id(),
        ];
      }
    }

    if ($regenerate) {
      $this->assertTrue($this->adapter->rebuild());
    }

    $this->assertTableContents($new_map, 'New root accounted for.');
  }

}
