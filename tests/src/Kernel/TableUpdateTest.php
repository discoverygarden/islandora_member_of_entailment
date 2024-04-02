<?php

namespace Drupal\Tests\islandora_member_of_entailment\Kernel;

use Drupal\islandora\IslandoraUtils;

/**
 * Test LUT maintance/building with updates.
 *
 * @group islandora_member_of_entailment
 */
class TableUpdateTest extends AbstractKernelTestBase {

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

  /**
   * Test moving item to a disjoint parent.
   *
   * @dataProvider buildType
   */
  public function testUpdateParent(bool $regenerate, bool $saving) {
    $diamond = $this->buildDiamond($regenerate, $saving);
    [$alpha, $bravo, $charlie, $delta, $echo] = $diamond;

    $new_root = $this->createNode();
    $new_root->save();

    $bravo->set(IslandoraUtils::MEMBER_OF_FIELD, ['target_id' => $new_root->id()]);
    $bravo->save();

    $map = [
      ['nid' => $bravo->id(), 'aid' => $new_root->id()],
      ['nid' => $charlie->id(), 'aid' => $alpha->id()],
      ['nid' => $delta->id(), 'aid' => $alpha->id()],
      ['nid' => $delta->id(), 'aid' => $new_root->id()],
      ['nid' => $delta->id(), 'aid' => $bravo->id()],
      ['nid' => $delta->id(), 'aid' => $charlie->id()],
      ['nid' => $echo->id(), 'aid' => $alpha->id()],
      ['nid' => $echo->id(), 'aid' => $new_root->id()],
      ['nid' => $echo->id(), 'aid' => $bravo->id()],
      ['nid' => $echo->id(), 'aid' => $charlie->id()],
      ['nid' => $echo->id(), 'aid' => $delta->id()],
    ];

    if ($regenerate) {
      $this->assertTrue($this->adapter->rebuild());
    }

    $this->assertTableContents($map, 'New root accounted for.');
  }

  /**
   * Test moving item to a disjoint parent.
   *
   * @dataProvider buildType
   */
  public function testUpdateMoveDiamond(bool $regenerate, bool $saving) {
    $diamond = $this->buildDiamond($regenerate, $saving);
    [, $bravo, $charlie, $delta, $echo] = $diamond;

    $bravo->set(IslandoraUtils::MEMBER_OF_FIELD, ['target_id' => $charlie->id()]);
    $bravo->save();

    $map = $this->getDiamondMap($diamond);

    // Just the new routes to charlie. Consistent ancestor (alpha) doesn't get
    // more dupes (than it already had).
    $map[] = ['nid' => $bravo->id(), 'aid' => $charlie->id()];
    $map[] = ['nid' => $delta->id(), 'aid' => $charlie->id()];
    $map[] = ['nid' => $echo->id(), 'aid' => $charlie->id()];

    if ($regenerate) {
      $this->assertTrue($this->adapter->rebuild());
    }

    $this->assertTableContents($map, 'New root accounted for.');
  }

}
