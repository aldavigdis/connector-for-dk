# wp-cron

Tengill fyrir DK samstillir upplýsingar á um klukkustundar fresti allan sólarhringinn í gegn um `wp-cron`. WordPress og flest hýsingarfyrirtæki gera ráð fyrir því að keyra `wp-cron` með því að láta vafra gesta á vefnum þínum kalla í `wp-cron.php` reglulega á bak við tjöldin.

Þessu geta fylgt ákveðin vandamál ef um er að ræða mikla umferð eða stóra vefverslun og sækja þarf og skrá mikið af gögnum — og þá duga 30 sekúndur ekki alltaf til að öll gögn séu sótt og samstillt. Einnig fer samstilling ekki fram ef engin umferð er á vefinn þinn og þessi leið er notuð.

### Keyrsla yfir HTTP

Á WordPress.org eru upplýsingar um hvernig hægt er að smíða cron-skipun sem kallar í wp-cron.php yfir HTTP. Ókosturinn við þessa leið er hinsvegar sá að sömu 30 sekúndna tímatakmarkanir gilda og áður, en kosturinn er sá að sú leið stendur ekki og fellur með því að vefverslunin þín fái umferð og hægt er að keyra þessa skipun á annari vél.

Nánari upplýsingar um þessa leið er hægt að finna á [https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/](https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/).

### Keyrsla með WP-CLI á Linux

Ef þú hefur aðgang að skel á vefþjóninum þínum, þá hefur þú mögulega aðgang að WP-CLI, sem hjálpar þér að nota WordPress í gegn um skipanalínuna og ennfremur er mögulegt að nota aðrar PHP-stillingar.

Til að slökkva á því að gestir geti kallað í `wp-cron.php` færirðu inn línuna `define( 'DISABLE_WP_CRON', true );` inn í `wp-config.php` eða slærð in WP-CLI skipunina `wp config set DISABLE_WP_CRON true --raw`.

#### Sem venjulegur notandi

Sláðu in `$ crontab -e` til að opna textaritil fyrir crontab-skrá þíns notanda. Neðst í skránna geturðu slegið inn eftirfarandi til að keyra wp-cron í allt að 50 mínútur í senn á klukkutíma fresti, 10 mínútur yfir heila tímann:

```crontab
10 * * * * php -d max_execution_time=3000 /usr/local/bin/wp cron event run --due-now --path="/home/notandi/www"
```

Hér er gert ráð fyrir því að vefrótin sé staðsett í möppunni `/home/notandi/www` en þessu þarftu að breyta þannig að það vísi í vefrótina sem vefurinn þinn er geymdur í.

#### Sem root

Eftirfarandi WP-CLI skipun keyrir wp-cron beint úr skelinni sem annar notandi sem `root` og slekkur á hámarks vinnslutíma á 50 mínútur. Þetta er hægt að setja inn í systemd-skriftu eða crontab fyrir `root`.

```sh
$ sudo -u www-data -- php -d max_execution_time=3000 /usr/local/bin/wp cron event run --due-now --path="/var/www/html"
```

Athugið að hér er gert ráð fyrir því að sudo-skipunin sé aðgengileg, að vefþjónninn keyri sem notandinn `www-data` og að vefrótin sé staðsett á `/var/www/html`. Einnig er gert ráð fyrir því að WP-CLI skipunin sé aðgengileg á `/usr/local/bin/wp`.

#### Stillingar í CPanel

Ef hýsingarfyrirtækið þitt notar CPanel er mögulega boðið upp á að stilla Cron þar inni. CPanel býður uppá upplýsingar um það á [https://docs.cpanel.net/cpanel/advanced/cron-jobs/](https://docs.cpanel.net/cpanel/advanced/cron-jobs/).
