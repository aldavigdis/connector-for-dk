# Þekkt vandamál og lausnir

## Reikningar skrifast ekki út, sjálfvirk samstilling hættir að virka og villa kemur þegar stillingar eru vistaðar eftir langa bið

Þetta hljómar eins og margar ótengdar villur, en það er nokkuð algengt að DK taki of langan tíma eða hreinlega hætti að svara. Á álagstímum uppúr hádegi getur t.d. tekið allt að tvær mínútur að útbúa reikning í DK.

Til að koma í veg fyrir að allt fari á hliðina út af þessu, þá takmarkar Tengill fyrir DK þann tíma sem fer í hverja tengingu við dkPlus API-viðmótið. Þetta eru 10 sekúndur til að sækja gögn (`GET`) og 15 sekúndur til að senda gögn (`POST`) yfir í DK. Þegar sá tími er liðinn er gert ráð fyrir því að ekki náist samband við bakendakerfi DK og tengingunni er lokað.

Margar vefhýsingar bjóða ekki upp á lengri vinnslutíma en 30 sekúndur í senn og það sama gildir um bakvinnsluferla á borð við wp-cron sem keyrðir eru á klukkustundar fresti.

Besta leiðin til að ráða fram úr því er að hafa samband við hýsingarfyrirtækið og fá aðstoð við að hækka PHP-stillinguna `max_execution_time`.

Nánar er fjallað um hvernig wp-cron er stillt af undir sér kafla, [wp-cron](cron.md).

## Ósamræmi milli VSK í WooCommerce og DK

WooCommerce getur lent í vandræðum með að áætla virðisaukaskatt á pantanir í íslenskum krónum, þannig að virðisaukaskattur sem sýndur er fyrir hverja pöntun í WooCommerce er krónu lægri en það sem kemur fram á tæknilega réttum reikningi úr DK.

Heildarupphæðin er þó sú sama og sú sem notuð var við greiðslu, þannig að það ætti ekki að vera missamræmi milli heildarupphæðar greiðslu og heildarupphæðar reiknings.

Þetta er mögulega hægt að laga með því að láta WooCommerce rúna niður VSK per línu en ekki fyrir heildarupphæð, sem virðist koma í veg fyrir þetta vandamál.

Það er hægt að stilla af með því að fara í stillingarnar fyrir WooCommerce, þar í *Tax* og taka hakið úr *„Round tax at subtotal level, instead of rounding per line“*.

Einnig er góð venja að stilla fastann `WC_TAX_ROUNDING_MODE` á `PHP_ROUND_HALF_UP` í wp-config.php til að tryggja að allar upphæðir 50 aurar og yfir séu námundaðaðar upp að næstu krónu. Það er hægt að gera með línunni `define( 'WC_TAX_ROUNDING_MODE', PHP_ROUND_HALF_UP );` eða í WP-CLI með eftirfarandi skipun:

```sh
$ wp config set WC_TAX_ROUNDING_MODE PHP_ROUND_HALF_UP --raw
```

Einingaverð (línuverð deilt í fjölda) og afslættir sendast til DK án VSK og DK sér um að bæta VSK-upphæð ofan á það verð. Tengill fyrir DK notar þar að auki 24 aukastafi við reikniaðgerðir þar sem þess er krafist á meðan WooCommerce notar 6 aukastafi.
