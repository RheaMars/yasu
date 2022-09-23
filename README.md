# yasu

This is a project that happened to obsess the contributors on a greek island in the summer of 2022: _figure out how to generate and solve sudoku boards of arbitrary size, without looking at any existing theory of sudoku_. Obviously, the kind of vacation that inspires one to reinvent the wheel.

If you're a PHP person you'll probably know how to get the whole thing up and running. If you're like the person writing this README, you'd be well advised to make sure you have the [PHP version 8.1](https://www.php.net/downloads.php) installed, just to be sure that you have all the current goodies, then clone the repository. [Visual Studio Code](https://code.visualstudio.com/), with a couple of appropriate PHP plug-ins, is not a bad choice of IDE if you feel like fooling around with the code but you don't have access to fancy things like [PhpStorm](https://www.jetbrains.com/phpstorm/) or similar.

You can then run [PHP's built-in server](https://www.php.net/manual/en/features.commandline.webserver.php) by

```...:~yasu$ php -S 127.0.0.1:8000```

and load the frontend on http://127.0.0.1:8000/ on your browser of choice, to actually play. There's no need to bother with heavy-duty tools like [XAMPP](https://www.apachefriends.org/) -- which, in the experience of the contributors, isn't even guaranteed to work out of the box in such systems as Ubuntu 18.04...

A propos Ubuntu, you will have to install [GMP](https://www.php.net/manual/en/class.gmp.php) extra, by
```sudo apt install php8.1-gmp```

Let's all hope that this README gets updated soon by a more knowledgeable person -- till then, have fun, with or without sudokus!...