# Default Content

## CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Usage



## Introduction
_A default content solution for Drupal 8_

[Default Content][1] allows you to export content along with site configuration information. It does so by leveraging core's serialization and hal modules. It supports entity-references between content as well as files if you have File entity. Content export works with a set of drush commands (more on those below). Content import happens automatically as part of site installation. The import process scans all modules and imports any content found that is located in the expected file path and using the expected .json file structure. (See detailed information below)

###  Features

* Supports entity-references between content

* Supports files if you have File entity

* Easily export your content and its dependencies to yml using drush commands

### Additional Information
* For a full description of the module, visit the project page:
  https://www.drupal.org/project/default_content

* To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/default_content


## Requirements
* Drupal 8
* core only
  * [HAL (Hypertext Application Language) module][2] must be enabled
  * [Serialization module][3] must be enabled

## Installation
Install as you would normally install a contributed Drupal module. Visit:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
for further information.

## Configuration
The module has no menu or modifiable settings. There is no configuration. When the default_content module is enabled, the core HAL and Serialization modules must also be enabled. If they are currently disabled, activating default_content will give you the option to enable them.


## Usage

For any module that requires default content, the export process is as follows:
1) Generate a hal+json file for each entity to be exported using the drush commands described below.
2) The entity .json files must be stored in module subdirectories using the following structure: `content/{entity type}/{filename}`, where `{entity type}` will be one of node, taxonomy_term, etc. Filename should be `{entity ID}.json`. For example, a Basic Page with the node ID of 23 would be stored in:
`.../modules/custom/someCustomModule/node/23.json`

Other examples:

 - `modules/default_content_test/content`
 - `modules/default_content_test/content/node`
 - `modules/default_content_test/content/node/imported.json`
 -  `modules/default_content_test/content/taxonomy_term`
 - `modules/default_content_test/content/taxonomy_term/tag.json`

3) Once your custom module is enabled, the entities you exported will be imported to the new environment during new site installation.

### Note

At the moment these files need to be hand-created or exported using the Rest, Hal and Serialization modules.
Note that the default functionality of the Hal module is to make all links point to the origin site's FDQN.

The default_content module expects these (at this stage) to be relative to http://drupal.org as there is no point in having default content that can only be re-imported on the originating site.

Note that imported.json contains a node with a term reference field that includes a reference to the term in tag.json.

The Gliph library (in 8.x core) is used to resolve the dependency graph, so in this case the term is imported first so that the reference to it is created in the node.

### Drush Commands

#### Exports a single entity

##### default-content-export

Arguments:
- **entity_type:** The entity type to export.
- **entity_id:** The ID of the entity to export.
options:
- **file:** Write out the exported content to a file instead of stdout
aliases: dce
- **required-arguments:** 2

Example:
```
$ drush dce node 123 my_default_content_module
```

Change the entity type, as we have them in the system.
```
$ drush dce node <node id> my_default_content_module
$ drush dce taxonomy_term <taxonomy term id> my_default_content_module
$ drush dce file <file id> my_default_content_module
$ drush dce media <media id> my_default_content_module
$ drush dce menu_link_content <menu link id> my_default_content_module
$ drush dce block_content <block id> my_default_content_module
```

#### Exports an entity and all its referenced entities

##### default-content-export-references

Arguments:
- **entity_type:** The entity type to export.
- **entity_id:** The ID of the entity to export.
options:
- **folder:** Folder to export to, entities are grouped by entity type into directories.
aliases: dcer
- **required-arguments:** 1

Example:
```
$ drush dcer node 123 my_default_content_module
```

#### Exports all content defined in a module info file.

##### default-content-export-module

Arguments:
- module: The name of the module.
- aliases: dcem
- required-arguments: 1

Example:
```
$ drush dcem my_default_content_module
```

And add the UUID of entities in my_default_content_module.info.yml file.

```
default_content:
  node:
    - c9a89616-7057-4971-8337-555e425ed782
    - b6d6d9fd-4f28-4918-b100-ffcfb15c9374
  file:
    - 59674274-f1f5-4d6a-be00-fecedfde6534
    - 0fab901d-36ba-4bfd-9b00-d6617ffc2f1f
  media:
    - ee63912a-6276-4081-93af-63ca66285594
    - bcb3c719-e266-45c1-8b90-8f630f86dcc7
  menu_link_content:
    - 9fbb684c-156d-49d6-b24b-755501b434e6
    - 19f38567-4051-4682-bf00-a4f19de48a01
  block_content:
    - af171e09-fcb2-4d93-a94d-77dc61aab213
    - a608987c-1b74-442b-b900-a54f40cda661
```

## To do

UI for easily exporting?

[![Build Status](https://travis-ci.org/larowlan/default_content.svg?branch=8.x-1.x)](https://travis-ci.org/larowlan/default_content)

[1]: https://www.drupal.org/project/default_content "Default Content"
[2]: https://www.drupal.org/docs/8/core/modules/hal "HAL (Hypertext Application Language) module"
[3]: https://www.drupal.org/docs/8/core/modules/serialization "Serialization module"
