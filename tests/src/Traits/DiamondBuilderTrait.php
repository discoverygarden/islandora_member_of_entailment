<?php

namespace Drupal\Tests\islandora_member_of_entailment\Traits;

use Drupal\islandora\IslandoraUtils;

/**
 * Helper trait for update/delete tests.
 */
trait DiamondBuilderTrait {

  /**
   * Build out base hierarchy with which to test.
   *
   * @return \Drupal\node\NodeInterface[]
   *   The nodes in the built-out hierarchy.
   *
   * @see \Drupal\Tests\islandora_member_of_entailment\Kernel\TableCreateTest::testTransitionViaDiamondCreation()
   */
  public function buildDiamond(bool $regenerate, bool $saving) : array {
    $this->savingSetup($saving);
    $alpha = $this->createNode();
    $alpha->save();
    $bravo = $this->createNode();
    $bravo->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($alpha);
    $bravo->save();
    $charlie = $this->createNode();
    $charlie->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($alpha);
    $charlie->save();
    $delta = $this->createNode();
    $delta_members = $delta->get(IslandoraUtils::MEMBER_OF_FIELD);
    $delta_members->appendItem($bravo);
    $delta_members->appendItem($charlie);
    $delta->save();
    $echo = $this->createNode();
    $echo->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($delta);
    $echo->save();

    if ($regenerate) {
      $this->assertTrue($this->adapter->rebuild());
    }

    $this->assertTableContents(
      $this->getDiamondMap([$alpha, $bravo, $charlie, $delta, $echo]),
      'Has the expected contents.',
    );

    return [$alpha, $bravo, $charlie, $delta, $echo];
  }

  /**
   * Helper, build out the base mapping, as we may want to derive others.
   *
   * @param array $diamond
   *   The elements in the base diamond structure.
   *
   * @return array[]
   *   The mapping.
   */
  public function getDiamondMap(array $diamond) : array {
    [$alpha, $bravo, $charlie, $delta, $echo] = $diamond;
    return [
      ['nid' => $bravo->id(), 'aid' => $alpha->id()],
      ['nid' => $charlie->id(), 'aid' => $alpha->id()],
      ['nid' => $delta->id(), 'aid' => $alpha->id()],
      // XXX: Yes, expecting multiple of these, with multiple routes back.
      ['nid' => $delta->id(), 'aid' => $alpha->id()],
      ['nid' => $delta->id(), 'aid' => $bravo->id()],
      ['nid' => $delta->id(), 'aid' => $charlie->id()],
      ['nid' => $echo->id(), 'aid' => $alpha->id()],
      // XXX: Yes, expecting multiple of these, with multiple routes back.
      ['nid' => $echo->id(), 'aid' => $alpha->id()],
      ['nid' => $echo->id(), 'aid' => $bravo->id()],
      ['nid' => $echo->id(), 'aid' => $charlie->id()],
      ['nid' => $echo->id(), 'aid' => $delta->id()],
    ];
  }

}
