# dice-poker

A server-rendered dice poker game written in pure PHP.

There is **no JavaScript** in this project.

The player rolls five dice up to three times, holds selected dice between rolls and then scores the final hand.

## Features

- pure PHP game logic
- no JavaScript
- 5 dice
- up to 3 rolls
- hold selected dice between rolls
- secure dice rolls with `random_int()`
- automatic hand evaluation
- Pair
- Two pair
- Three of a kind
- Small straight
- Full house
- Four of a kind
- Large straight
- Yahtzee
- fallback high-dice score
- score history statistics
- average score
- best score
- Yahtzee counter
- strong-hand streak
- best streak
- CSRF protection
- POST / Redirect / GET flow
- responsive CSS
- no framework
- no database

## Requirements

PHP 8.1+

## Run

```bash
php -S localhost:8000
```

Open:

```text
http://localhost:8000
```

## How to play

1. Press **Roll dice**.
2. Select the checkboxes under dice you want to keep.
3. Press **Roll again**.
4. Repeat until you are happy with the hand or use all three rolls.
5. Press **Score hand**.

## Combinations

```text
Yahtzee             50
Large straight      40
Four of a kind      30
Full house          25
Small straight      20
Three of a kind     18
Two pair            14
Pair                 8
High dice            sum of dice
```

### Large straight

```text
1 2 3 4 5
2 3 4 5 6
```

### Small straight

Any four consecutive values, for example:

```text
1 2 3 4
2 3 4 5
3 4 5 6
```

## Pure PHP architecture

PHP handles:

- random dice rolls
- held dice
- roll count
- hand evaluation
- combination priority
- scoring
- statistics
- streaks
- session state
- CSRF protection

State is stored in:

```php
$_SESSION['dice_poker_round']
```

and:

```php
$_SESSION['dice_poker_stats']
```

Each action is a normal HTML `POST` request followed by a redirect.

## Project structure

```text
dice-poker/
├── index.php
├── functions.php
├── style.css
└── README.md
```

## Hosting

GitHub can host the repository source, but GitHub Pages cannot execute PHP.

Use PHP-capable hosting, a VPS, Docker/PHP-Apache, or PHP's built-in development server.

## License

MIT
