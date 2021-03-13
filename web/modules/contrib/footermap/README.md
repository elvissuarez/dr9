# Footermap

Footermap is a dynamic site map generator for Drupal that allows a site builder to place a "footer" site map block into any region.

## Installation

* Extract the files to the appropriate location on your web server (modules by default).
* Enable the module from the modules page.

## Configuration

* Place a Footermap block instance on the *Block layout* administration page.
* Configure the behavior of this footermap block and click *Save Configuration*.

### Caching

Footermap uses the Core block caching for language content. This should be handled automagically for multilingual sites. Please report any issues.

### Recurse Limit

Limit the how deep to recurse into the menu tree. By default, Footermap will crawl through each menu item's children.

### Enable Menu Heading

Display the menu title above each list of menu links. This helps organize links semantically, but is not necessary if you only include one menu.

### Top Menu Link

Provide a plugin id to use as the top-level of the menu tree. You must also select the appropriate menu from Available Menus below. This is an advanced setting.

The Plugin ID is most often the same as a menu link route name.

Example: `help.main` is the plugin ID for `help.main` route, which is the route for the "Help" menu item.

### Available Menus

Choose which menus to display in this block instance. If your web site does not have any menu items this may be blank. By default, Drupal will install several primary menus.

## Theming

Each primary menu is rendered as ```nav.footermap-col``` and its content rendered as children of that element. By default, ```.footermap-col``` is float left to keep all columns inline.

A footermap column is separated between a ```h3``` and ```ul.footermap-header```, which iterates over a list of footermap items.

A footermap item is represented as a ```li.footermap-item```, and any children are rendered inside the list item element within a list element.

See the Twig templates for additional details with an example of drastically changing how footermap items are rendered.

### Bootstrap 3 Navbar

It is possible to create a dropdown navbar for your menu using Bootstrap 3 and Footermap by adding the appropriate classes.


## Troubleshooting

There are no known issues, but please be sure to look at your Apache, IIS, and/or PHP error log for details when [reporting issues](http://drupal.org/project/issues/footermap).

Please note that Footermap generates the site map based on the *anonymous user*, and any menu router or menu links that are not accessible to the anonymous user will not be displayed.