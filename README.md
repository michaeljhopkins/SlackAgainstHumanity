## Slack Against Humanity (A Laravel Package) v0.5

<img src="http://i.imgur.com/jS0h048.png">

Slack Against Humanity is a Slack/Hubot implementation of the popular card game, [Cards Against Humanity](http://cardsagainsthumanity.com). Commands are issued in your slack channel via [Custom Slash Commands](https://api.slack.com/slash-commands), Github's [Hubot](https://github.com/github/hubot), or a combination of both and then passed to this package. Cards will be dealt, Judges are picked at random, and who plays what card is kept secret until the end (if they win!).

---

- This is **Cards Against Humanity**. If you have sensibilities that are easily offended, this game is NOT FOR YOU. 
- All commands are issued via GET and POST requests against a web API
- **763** included cards!
- 103 **Black Cards** 
- 660 **White Cards**
- A randomly picked Judge each round
- Ability to enter and exit play **on your time**
- Laravel **5.x** compatibility
- Intellegence on who is actually playing or not
- with many more features in the works.

---

[![Latest Stable Version](https://poser.pugx.org/hopkins/slack-against-humanity/version.svg)](https://packagist.org/packages/hopkins/slack-against-humanity) 
[![Total Downloads](https://poser.pugx.org/hopkins/slack-against-humanity/downloads.svg)](https://packagist.org/packages/hopkins/slack-against-humanity)
[![Latest Unstable Version](https://poser.pugx.org/hopkins/slack-against-humanity/v/unstable.svg)](//packagist.org/packages/hopkins/slack-against-humanity) 
[![License](https://poser.pugx.org/hopkins/slack-against-humanity/license.svg)](https://packagist.org/packages/hopkins/slack-against-humanity)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/7f2ad83c-308b-45cd-bfa9-aee3399eb7bc/small.png)](https://insight.sensiolabs.com/projects/7f2ad83c-308b-45cd-bfa9-aee3399eb7bc)

Anybody that wants to play needs to be dealt in. A check happens on every deal, and every new round that 3 people are playing. CAH with 2 people is kinda boring. Don't like your cards? Too bad, no cheating here.

<img src="http://i.imgur.com/lmHNLDJ.png">

Once there have been dealt in, a Judge is chosen at random, and a Black card is chosen at random. Everybody except the Judge needs to play a white card from their hand. 

<img src="http://i.imgur.com/AbgUdIO.png">

After each player plays a card a check happens to see if everybody who can play, did play. If they did, it's time to pick a winner! Only the Judge is allowed to choose the winner. 

<img src="http://i.imgur.com/EB40EKX.png">

Everybody who played a card is then dealt a new card. And it starts all over again.

<img src="http://i.imgur.com/8sEwCoi.png">

---

#Installation

In it's current state, this package expects that you have set up [**Maknz/Slack**](https://github.com/maknz/slack) on your own. This includes setting up the facade `Slack::` as well as set up a **#cards** channel in Slack.
 
 Require this package in your `composer.json` and update composer.

```php
"hopkins/slack-against-humanity": "~0.5"
```

After updating composer, add the ServiceProvider to the providers array in `app/config/app.php`

```php
'Hopkins\SlackAgainstHumanity\Providers\SahServiceProvider',
```

Run the artisan command to bring the migrations and see files into your source db

```php
php artisan vendor:publish
```

Make a new **CardsController** that extends the controller in the package

```php

    <?php namespace Idop\Http\Controllers;

    use Hopkins\SlackAgainstHumanity\Game\BaseCardsController;

    class CardsController extends BaseCardsController{}
    
```

You will also need to add the below into your routes.php file

```php

    Route::group(['prefix' => 'cards'], function () {
        Route::get('deal', 'CardsController@deal');
        Route::get('start', 'CardsController@start');
        Route::get('show', 'CardsController@show');
        Route::post('play', 'CardsController@play');
        Route::post('choose', 'CardsController@choose');
        Route::get('quit', 'CardsController@quit');
    });
    
```

Players can use public messages to trigger all of these endpoints except for `Route::post('play',...)`. This needs to played in secret. A [Custom Slash Command](https://api.slack.com/slash-commands) is perfect for this. 
<img src="http://i.imgur.com/aNea4AX.png">

We use our hubot, Sterling, to play the other commands. The coffee script is a fairly simple one to make it work.

```coffeescript

    # Description:
    #   Cards Against Humanity!
    #
    # Dependencies:
    #   None
    #
    # Commands:
    #   hubot cards deal - adds you to the current cards game
    #   hubot cards quit - removes you from the current cards game
    #   hubot cards show - messages you your cards again incase you forgot
    #   /cards {id} - plays the id of your chosen card for the current round (a slack slash command is taking care of it though)
    #   hubot cards choose {id} - The current round's judge chooses the best card against the pre determined black card
    #
    # Author:
    #   michael-hopkins
    deal = "http://url.com/cards/deal"
    quit = "http://url.com/cards/quit"
    choose = "http://url.com/cards/choose"
    show = "http://url.com/cards/show"

    module.exports = (hubot) ->
        hubot.respond /cards deal/i, (msg) ->
            user = msg.message.user.name
            message = msg.message.text
            room = msg.message.user.room
            data = {'user_name': user,'message': message,'room': room,'directive': 1}
            hubot.http(deal).query(data).get() (err, res, body) ->

        hubot.respond /cards quit/i, (msg) ->
            user = msg.message.user.name
            message = msg.message.text
            room = msg.message.user.room
            data = {'user_name': user,'message': message,'room': room,'directive': 1}
            hubot.http(quit).query(data).get() (err, res, body) ->

        hubot.respond /cards show/i, (msg) ->
            user = msg.message.user.name
            message = msg.message.text
            room = msg.message.user.room
            data = {'user_name': user,'message': message,'room': room,'directive': 1}
            hubot.http(show).query(data).get() (err, res, body) ->

        hubot.respond /cards choose (.*)/i, (msg) ->
            user = msg.message.user.name
            message = msg.message.text
            room = msg.message.user.room
            cardId = msg.match[1]
            data = {'user_name': user,'message': message,'room': room,'directive': 1,'cardId': cardId}
            hubot.http(choose).query(data).get() (err, res, body) ->
    
```

# "Roadmap"

 * Add in tests to verify complete functioning package
 * Black cards that require 2 cards (and more) from each player
 * Using an image processing library to generate an actual "card" instead of just a message
 * Tie the `@user++` message at the end of the round into the [Hubot-PlusPlus](https://github.com/ajacksified/hubot-plusplus) script
 * Remove or build in, dependancy of maknz/flash, instead of trusting it's already implemented
 * Add configuration for which room to play SAH in instead of assuming `#cards`
 * Automate controllers and routes so the user doesn't need to interfere

# License

This project is licensed using [DBAD](http://www.dbad-license.org/). Go have a blast.

