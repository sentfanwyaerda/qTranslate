#qTranslate
**a way to provide multi-lingual texts**

*qTranslate* is a simplified method of the [qTranslate-plug-in](https://wordpress.org/plugins/qtranslate/) for [Wordpress](http://www.wordpress.org/) by [Qian Qin](http://www.qianqin.de/qtranslate/), which appears to be discontinued and reborn into [qTranslate-X](http://github.com/qTranslate-Team/qtranslate-x).

This script contains only the PHP method for use in non-wordpress enviroments, like the scripts and applications you write yourself.

```php
$str = '[:en]English Text[:de]Deutsch Scrifft[:nl]Nederlandse paragraaf[:]';
print qTranslate::parse($str);
```

These features are supported:
* In-line syntax ``<!--:en-->English<!--:-->``
* In-line syntax ``[:en]English[:]``
* unclosed tags in syntax: ``<!--:-->`` and ``[:]`` close and specify the beginning of a non-language-specific portion of the text
