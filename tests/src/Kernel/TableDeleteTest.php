<?php

namespace Drupal\Tests\islandora_member_of_entailment\Kernel;

/**
 * Test LUT maintenance/building with deletion.
 */
class TableDeleteTest extends AbstractBase {

  use DiamondBuilderTrait;

  /**
   * Test deletion, with related updating of the LUT.
   *
   * @dataProvider buildType
   */
  public function testDelete(bool $regenerate, bool $saving) {
    $diamond = $this->buildDiamond($regenerate, $saving);
    [$alpha, $bravo, $charlie, $delta, $echo] = $diamond;

    $bravo->delete();

    if ($regenerate) {
      $this->assertTrue($this->adapter->rebuild());
    }

    $this->assertTableContents(
      [
        ['nid' => $charlie->id(), 'aid' => $alpha->id()],
        ['nid' => $delta->id(), 'aid' => $alpha->id()],
        ['nid' => $delta->id(), 'aid' => $charlie->id()],
        ['nid' => $echo->id(), 'aid' => $alpha->id()],
        ['nid' => $echo->id(), 'aid' => $charlie->id()],
        ['nid' => $echo->id(), 'aid' => $delta->id()],
      ],
      'Has the expected contents after deletion.',
    );
  }

}
