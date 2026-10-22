=== Flatsome Block Manager ===
Requires at least: 6.4
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later

Lists UX Blocks, plugin elements and Flatsome elements, each with On / Hidden / Off. Ships custom UX Builder elements, preset UX Blocks and Import templates.

== Installation ==

1. Plugins > Add New > Upload Plugin > choose flatsome-block-manager.zip > Install Now > Activate.
2. The Flatsome theme (or a child theme) must be active.
3. Open the "Block Manager" menu (right below the Flatsome menu).
4. The "Flatsome Elements" tab needs UX Builder to be opened once so the plugin can read the theme's element list.

== On / Hidden / Off ==

* On: normal use.
* Hidden: not listed in UX Builder's Add elements (for UX Blocks: not offered by the Block element picker). Existing uses still render.
* Off: like Hidden, and nothing renders on the site. Editors see a dashed placeholder in its place.

Layout elements (Section, Row, Column, Grid, Stack, Text, Block) are always On.
Turning off something that is in use asks for confirmation first.
UX Blocks are still created, edited and deleted from Flatsome's UX Blocks menu.

== Built-in elements ==

* Light Slider [fbm_light_slider]: zero-library infinite slider for banners and images (alias [fbm_light_carousel]).
* Smart Product Tabs Slider [uxb_smart_product_slider]: WooCommerce product tabs; the first tab renders on the server, the others load over AJAX.
* Animated Showcase [fbm_animated_showcase] with Showcase Step [fbm_showcase_step].
* Bento Card [fbm_bento_card], Pill Badge [fbm_pill_badge], Avatar Stack [fbm_avatar_stack].
* Step Progress Bar [ux_step_progress].

Elements use Flatsome / WooCommerce markup and classes, so they follow the site's colors and CSS. Their CSS/JS only loads when the element is on the page.

== Templates and preset UX Blocks ==

Every UX Block the plugin seeds has a matching template in templates/<slug>/. Each template is available in UX Builder › Add Content › Import ("Insert a template", empty pages only) and as a preset of the Section element (Add elements › Section), which can be inserted anywhere, any number of times.

== Upgrading from 1.x ==

* UX Blocks that were disabled (or hidden by a schedule) become Off. Schedules are dropped.
* The 1.x code-based element editor is removed together with its data (custom elements, versions, files in uploads/fbm-elements).
* Removed 1.x tags: guests see nothing, editors see a dashed placeholder. The admin shows a notice listing these tags.

== Uninstall ==

Deletes the On / Hidden / Off states, the recorded element list and the usage index. UX Blocks and page content are kept.

== Changelog ==

= 2.0.0 =
* Rewrite: the admin is now 3 list tabs (UX Blocks · Plugin elements · Flatsome elements), each row On / Hidden / Off.
* Added Light Slider, Smart Product Tabs Slider, Animated Showcase, Bento Card, Pill Badge, Avatar Stack and Step Progress Bar.
* Added preset UX Blocks with matching Import templates, also registered as Section presets.
* Removed the code editor, sandbox, versions, export/import, Gutenberg support, the Settings page and schedules.

= 1.0.0 =
* First release.
