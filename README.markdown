Syntax highlighting for Wordpress via Luminous
==============================================

## Installation
As well as this plugin, you need a copy of Luminous, get it from
[the website](http://luminous.asgaard.co.uk), or
[github](https://github.com/markwatkinson/luminous).

Place the plugin in your wp-content/plugins directory, and place your
copy of Luminous in the same directory as the plugin's php file (or symlink
or whatever).

Activate the plugin from your Wordpress dashboard. There are various
configuration options added under the Plugins menu.

## Usage

    [sourcecode language='c']
    printf("hello world\n");
    [/sourcecode]

Note that anything before the first linebreak after the opening tag, and
anything after the last line break before the closing tag is truncated.

Valid attributes are:
language=code (corresponds to a [language code](http://luminous.asgaard.co.uk/index.php/page/languages)).
height=[0-9]+ (widget height in pixels, optional, this overrides the configuration setting)
escaped=true|false (if your source code is HTML entity escaped, set this to true. Optional, default: false)

## Known issues
The visual editor will gobble your code and mess it up. There are probably
other plugins that give better control over the visual editor.

