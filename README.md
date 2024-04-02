# Islandora `field_member_of` Entailment (`islandora_member_of_entailment`)

## Introduction

Maintain flattened representation of the hierarchy of nodes represented by
Islandora's `field_member_of` relationship.

# Requirements

Presently, only PostgreSQL is supported.

This module requires the following modules/libraries:

- [Islandora](https://github.com/Islandora/islandora)
- Drupal's core `node` module

## Views integration

Node/"Content" views can be joined by adding relationships. For example, to
interact with parents if the nodes in the current result set, you could add:

- The "Islandora Member Of Entailment: Node" (/"IMoE: Node") relationship
- Making use of the "IMoE: Node" relationship, add "Islandora Member Of
    Entailment"'s "Ancestor node" relationship; and finally,
- Add fields/filters/etc making use of the "Ancestor node" relationship, as
    desired.

Similarly, it is possible to flip around the direction of the relationships to
target descendents.


## Installation

Install as usual, see
[this]( https://www.drupal.org/docs/extending-drupal/installing-modules) for
further information.

## Troubleshooting/Issues

Having problems or solved a problem? Contact [discoverygarden](http://support.discoverygarden.ca).

## Maintainers/Sponsors

This project has been sponsored by:

* [discoverygarden](http://wwww.discoverygarden.ca)

## Development

If you would like to contribute to this module, please check out our helpful
[Documentation for Developers](https://github.com/Islandora/islandora/wiki#wiki-documentation-for-developers)
info, [Developers](http://islandora.ca/developers) section on Islandora.ca and
contact [discoverygarden](http://support.discoverygarden.ca).

## License

[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
