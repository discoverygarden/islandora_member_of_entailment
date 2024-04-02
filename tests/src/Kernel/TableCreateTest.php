<?php

namespace Drupal\Tests\islandora_member_of_entailment\Kernel;

use Drupal\islandora\IslandoraUtils;

/**
 * Test generation and maintenance of table.
 */
class TableCreateTest extends AbstractBase {

  /**
   * Test basic node creation.
   *
   * @dataProvider buildType
   */
  public function testBasicCreation(bool $regenerate, bool $saving) : void {
    $this->savingSetup($saving);
    $alpha = $this->createNode();
    $this->assertEquals($saving ? SAVED_UPDATED : SAVED_NEW, $alpha->save());
    $bravo = $this->createNode();
    $bravo->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($alpha);
    $this->assertEquals($saving ? SAVED_UPDATED : SAVED_NEW, $bravo->save());

    if ($regenerate) {
      $this->assertTrue($this->adapter->rebuild());
    }

    $this->assertTableContents(
      [
        ['nid' => $bravo->id(), 'aid' => $alpha->id()],
      ],
      'Has the expected items.',
    );
  }

  /**
   * Test basic transitive node creation.
   *
   * @dataProvider buildType
   */
  public function testBasicTransitiveCreation(bool $regenerate, bool $saving) : void {
    $this->savingSetup($saving);
    $alpha = $this->createNode();
    $alpha->save();
    $bravo = $this->createNode();
    $bravo->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($alpha);
    $bravo->save();
    $charlie = $this->createNode();
    $charlie->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($bravo);
    $charlie->save();

    if ($regenerate) {
      $this->assertTrue($this->adapter->rebuild());
    }

    $this->assertTableContents(
      [
        ['nid' => $bravo->id(), 'aid' => $alpha->id()],
        ['nid' => $charlie->id(), 'aid' => $alpha->id()],
        ['nid' => $charlie->id(), 'aid' => $bravo->id()],
      ],
      'Has the expected contents.',
    );

  }

  /**
   * Test basic multiple node creation.
   *
   * @dataProvider buildType
   */
  public function testBasicMultipleCreation(bool $regenerate, bool $saving) : void {
    $this->savingSetup($saving);
    $alpha = $this->createNode();
    $alpha->save();
    $bravo = $this->createNode();
    $bravo->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($alpha);
    $bravo->save();
    $charlie = $this->createNode();
    $charlie->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($alpha);
    $charlie->save();

    if ($regenerate) {
      $this->assertTrue($this->adapter->rebuild());
    }

    $this->assertTableContents(
      [
        ['nid' => $bravo->id(), 'aid' => $alpha->id()],
        ['nid' => $charlie->id(), 'aid' => $alpha->id()],
      ],
      'Has the expected contents.',
    );

  }

  /**
   * Test basic multiple node creation.
   *
   * @dataProvider buildType
   */
  public function testDiamondCreation(bool $regenerate, bool $saving) : void {
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

    if ($regenerate) {
      $this->assertTrue($this->adapter->rebuild());
    }

    $this->assertTableContents(
      [
        ['nid' => $bravo->id(), 'aid' => $alpha->id()],
        ['nid' => $charlie->id(), 'aid' => $alpha->id()],
        ['nid' => $delta->id(), 'aid' => $alpha->id()],
        // XXX: Yes, expecting multiple of these, with multiple routes back.
        ['nid' => $delta->id(), 'aid' => $alpha->id()],
        ['nid' => $delta->id(), 'aid' => $bravo->id()],
        ['nid' => $delta->id(), 'aid' => $charlie->id()],
      ],
      'Has the expected contents.',
    );

  }

  /**
   * Test basic multiple node creation.
   *
   * @dataProvider buildType
   */
  public function testTransitionViaDiamondCreation(bool $regenerate, bool $saving) : void {
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
      [
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
      ],
      'Has the expected contents.',
    );
  }

}
