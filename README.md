# PhergieWolframAlpha

[Phergie](http://github.com/phergie/phergie-irc-bot-react/) plugin to access the Wolfram Alpha API trough IRC.

## Install

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `~`.

```
composer require hashworks/phergie-plugin-wolfram-alpha
```

See Phergie documentation for more information on
[installing and enabling plugins](https://github.com/phergie/phergie-irc-bot-react/wiki/Usage#plugins).

## Configuration

```php
// dependency
new \Phergie\Irc\Plugin\React\Command\Plugin,
new \hashworks\Phergie\Plugin\WolframAlpha\Plugin(array(
    'appid' => 'your-app-id', // Required. Set your appID. https://developer.wolframalpha.com/portal/apisignup.html
    'useMetric' => true // Default. Use metric system for results.
    'processingReply' => true // Default. Show or hide the 'Processing ...' reply.
)),
```

## Examples

```
<hashworks> wolfram-alpha population of germany
<Phergie> Processing...
<Phergie> 81.6 million people (world rank: 16th) (2014 estimate)
<hashworks> wolfram-alpha 200€ in BTC
<Phergie> Processing...
<Phergie> ฿0.92 (bitcoins)
<hashworks> 20 = 2500/x
<Phergie> Processing...
<Phergie> x = 125
```
