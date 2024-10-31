=== Paste JSON text ===
Contributors: tmatsuur
Donate link: https://elearn.jp/wpman/column/paste-json-text.html
Tags: post, page, admin
Requires at least: 3.3.0
Tested up to: 6.0.0
Stable tag: 2.0.0

This plugin uses a post editor to copy and paste post content between sites.

== Description ==

Add a panel/widget to the post editor in the admin panel for copying and pasting post content.
Pressing the Copy From button will copy the post content including title, body, categories, and tags to the clipboard. At the copy destination, open a new post, parse the clipboard contents, and paste them into the post editor.

= Some features: =

* A post content is changed into a JSON text(include: title, content, post-name, categories, post-tags, meta values).
* A JSON text is pasted to the admin post editor.

= Support =

* Japanese - https://elearn.jp/wpman/column/paste-json-text.html

= Translators =

* Japanese(ja) - [Takenori Matsuura](https://12net.jp/)

You can send your own language pack to me.

Please contact to me.

* https://12net.jp/ (ja)
* @tmatsuur on twitter.

= Contributors =

* [Takenori Matsuura](https://12net.jp/)

== Installation ==

* A plug-in installation screen is displayed on the WordPress admin panel.
* It installs it in `wp-content/plugins`.
* The plug-in is made effective.

== Frequently Asked Questions ==

== Screenshots ==

1. JSON text pad widget in the admin post/page editor(classic editor).
2. Push 'Change JSON' button, a post content is changed into a JSON text(classic editor).
3. Push 'JSON paste' button, a JSON text is pasted to the admin post/page editor(classic editor).
4. In the block editor, click on the clipboard icon next to the settings icon.
5. Click on the "Copy content" button in the panel that opens.
6. Switch to the destination post editor, paste the clipboard contents into the "JSON text" text box, and click the "Paste to post" button.

== Changelog ==

= 2.0.0 =
* Block editor is now supported.

= 1.1.3 =
* Fixed duplicate custom fields when pasting.

= 1.1.2 =
* Fixed a problem that occurred in version 5.3.

= 1.1.1 =
* Copy to clipboard using clipboard.js of version 5.2.

= 1.1.0 =
* Added paste_json_extra filter to support custom forms.

= 1.0.7 =
* Bug fix: It changed so that it html decode at the time of the paste of meta value.

= 1.0.6 =
* Update: It corresponds to version 3.5.

= 1.0.5 =
* Bug fix: P tag closed in this widget.

= 1.0.4 =
* Update: It corresponds to version 3.4.2.

= 1.0.3 =
* Bug fix: The wrong name was corrected.

= 1.0.2 =
* Bug fix: A tag can be deleted after paste.

= 1.0.1 =
* Bug fix: Paste to multiple post meta.

= 1.0.0 =
* The first release.

== Credits ==

This plug-in is not guaranteed though the user of WordPress can freely use this plug-in free of charge regardless of the purpose.
The author must acknowledge the thing that the operation guarantee and the support in this plug-in use are not done at all beforehand.

== Contact ==

email to takenori.matsuura[at]gmail.com
twitter @tmatsuur
