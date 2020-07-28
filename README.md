# Finskore Game Host

[Finskore](https://github.com/mike-healy/finskore) is a browser based app for scoring games of Finska (aka Klop & Molkky).
It runs fully client side, however this project will act as a backend to allow multiple players to follow the game on their own device.

Finskore Host will receive game state from the player keeping score, and push it out to subscribers via web sockets to keep them in sync.
This can help in bigger groups to save the scorer from keeping everyone up to date.

The client app will need to be updated to support connecting to this project when it's done.

* Laravel
* Redis
* Third party event handler TBD
