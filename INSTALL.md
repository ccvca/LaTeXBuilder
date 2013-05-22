Installation
============
* Copy the whole `LaTeXBuilder` folder to the `plugin` folder.
* Copy the **whole** reository from [pdf.js](http://mozilla.github.io/pdf.js/) and save it so that is
accessable via http://(subdomain.)example.com/pdf.js/ or you need to change the path in `LaTeXBuilder/init.js`, too.
* Make sure, that `pdflatex` is availiable to the php-user.
* If you want to use `SyncTeX` the `synctex` command need to be availiable as well.

Config
======
* shell escape and additional arguments for LaTeX could be set in `config.php`
