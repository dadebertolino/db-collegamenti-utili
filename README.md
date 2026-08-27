# DB Collegamenti Utili

Plugin WordPress per gestire **bandi/PON e collegamenti utili** e mostrarli in home tramite un **carosello accessibile**. Pensato per siti scolastici che usano il tema **Design Scuola Italia** (Bootstrap Italia / linee guida AGID).

- **Autore:** Davide Bertolino — [davidebertolino.it](https://www.davidebertolino.it)
- **Licenza:** GPL v2 o successiva
- **Requisiti:** WordPress 5.8+, PHP 7.4+
- **Niente registrazioni, niente nag, niente trucchi.**

## Cosa fa

1. Aggiunge un tipo di contenuto **"Collegamenti utili"** dove per ogni voce puoi inserire:
   - **Logo principale** (immagine in evidenza) → mostrato nella pagina di dettaglio;
   - **Logo per il carosello** (secondo logo) → mostrato in home;
   - **Testo alternativo (alt)** obbligatorio per l'accessibilità;
   - **Descrittivo** (editor: che cos'è il bando/PON);
   - **Link esterno** opzionale (es. Comune, Scuola in Chiaro);
   - **Documenti allegati ripetibili** con titolo, descrizione e data (lettera di candidatura, avviso, autorizzazione…);
   - flag **"Mostra nel carosello"** e **ordine**.
2. Genera una **pagina di dettaglio** in stile card Bootstrap Italia con la lista allegati scaricabili.
3. Fornisce lo shortcode **`[db_collegamenti_utili]`** per il carosello in home.

## Shortcode

```
[db_collegamenti_utili]
```

Attributi opzionali:

| Attributo | Default | Descrizione |
|-----------|---------|-------------|
| `titolo` | `Collegamenti utili` | Titolo sopra il carosello (vuoto per nasconderlo). |
| `per_view` | `3` | Loghi visibili contemporaneamente su desktop (1–4). Scende a 2 sotto i 992px e a 1 sotto i 768px. |
| `autoplay` | `false` | Scorrimento automatico. Off di default per rispetto WCAG. |
| `intervallo` | `5` | Secondi fra uno scorrimento e l'altro quando `autoplay` è attivo (minimo 3). |

Esempio:

```
[db_collegamenti_utili titolo="COLLEGAMENTI UTILI" per_view="3" autoplay="true" intervallo="6"]
```

Nel carosello compaiono **solo** le voci con il flag "Mostra nel carosello" attivo, ordinate per il campo *ordine*.

### Quando il carosello non scorre

Il carosello scorre se le voci pubblicate e spuntate sono **più** di quelle visibili insieme — e quante ne stanno insieme dipende dalla larghezza dello schermo: `per_view` su desktop, 2 sotto i 992px, 1 sotto i 768px.

Ne segue che lo stesso carosello può comportarsi diversamente a seconda del dispositivo. Con `per_view="3"` e 2 voci, per esempio, su desktop non c'è nulla da scorrere, mentre su telefono le pagine sono due. Quando alla larghezza corrente esiste una sola pagina il plugin nasconde frecce e pulsante di pausa e centra i loghi, invece di mostrare comandi che non rispondono; appena la finestra si stringe abbastanza, i comandi ricompaiono da soli.

Se vuoi lo scorrimento anche su desktop con poche voci, abbassa `per_view`.

### Scorrimento automatico e accessibilità

Con `autoplay="true"` compare accanto alle frecce un pulsante **pausa/riprendi**, raggiungibile da tastiera. Non è un vezzo: il criterio **WCAG 2.2.2 "Pause, Stop, Hide" è di livello A**, il minimo richiesto ai siti della PA, e impone un comando esplicito per fermare qualsiasi contenuto che si muove da solo per più di cinque secondi. La pausa al passaggio del mouse da sola non basta.

Lo scorrimento si ferma inoltre da sé al passaggio del mouse e all'arrivo del focus da tastiera, e non parte affatto per chi ha attivato la riduzione delle animazioni nel sistema operativo (`prefers-reduced-motion`).

## Conformità (siti scolastici / PA)

- **WCAG 2.1 AA**: alt obbligatorio sui loghi, focus visibile da tastiera, `aria-label` sui link, autoplay disattivato di default, link esterni segnalati agli screen reader.
- **Riuso di Bootstrap Italia** del tema Design Scuola Italia: nessuna libreria JS/CSS duplicata, aspetto coerente col resto del sito.
- **Tracciabilità amministrativa**: ogni allegato ha titolo, descrizione e data.
- **Sicurezza**: nonce, sanitizzazione, controllo capability su ogni salvataggio.

## Installazione

1. Carica lo ZIP da *Plugin → Aggiungi nuovo → Carica plugin*.
2. Attiva.
3. Crea le voci in *Collegamenti utili*.
4. Inserisci `[db_collegamenti_utili]` nella home.

Gli aggiornamenti arrivano automaticamente dalle Release GitHub (`dadebertolino/db-collegamenti-utili`).

## Changelog

### 1.1.1

- **Correzione al comportamento introdotto nella 1.1.0 su schermi stretti.** Se il carosello avesse o no qualcosa da scorrere veniva deciso in PHP confrontando le voci con `per_view`, cioè con il valore *desktop*. Ma i breakpoint abbassano le colonne a 2 sotto i 992px e a 1 sotto i 768px: con 2 voci e `per_view="3"` il carosello è effettivamente fermo su desktop, mentre su telefono ha due pagine. Disattivando frecce e trascinamento a monte, su mobile le due voci restavano irraggiungibili e — per via del centraggio applicato al caso "statico" — venivano mostrate entrambe a metà, tagliate su tutti e due i lati.
- Frecce e trascinamento sono ora sempre attivi e li governa Splide, che conosce il breakpoint corrente. `frontend.js` nasconde i comandi e centra i loghi solo quando `Controller.getEnd()` dice che alla larghezza attuale esiste una sola pagina, ricalcolandolo a ogni ridimensionamento.
- I comandi restano nascosti anche finché Splide non è stato montato, così su un tema senza Splide non compaiono frecce inerti.
- Aggiunta l'opzione `rewind`, perché lo scorrimento automatico riparta dall'inizio anche quando il carosello non è in modalità ciclo.

### 1.1.0

- **Il carosello ora funziona davvero.** Nella 1.0.x non scorreva e le frecce erano inerti: il markup delegava l'inizializzazione al tema, ma l'inizializzatore di `bootstrap-italia.js` legge le classi `it-carousel-*` **sull'elemento Splide**, mentre il plugin le metteva sul wrapper padre. Non riconoscendo nulla, il tema montava Splide con tutte le opzioni a `undefined` (`type`, `perPage`, `gap`, `arrows`), da cui una sola slide a piena larghezza e i comandi bloccati. Il plugin monta ora Splide per conto proprio, con opzioni esplicite.
- Rimosso l'attributo `data-bs-ride`: apparteneva al carousel di Bootstrap 5, mentre il tema usa Bootstrap Italia 1.x (Bootstrap 4) e affida i caroselli a Splide. Era una leva scollegata da qualsiasi meccanismo.
- L'elemento interno usa la classe `dbcu-splide` invece di `splide`, così `bootstrap-italia.js` non vi monta sopra una seconda istanza. La libreria resta quella del tema: nessuna duplicazione.
- `per_view` ha finalmente effetto, con adattamento a 2 colonne sotto i 992px e a 1 sotto i 768px.
- Nuovo attributo `intervallo` per la cadenza dello scorrimento automatico.
- Con `autoplay="true"` compare un pulsante **pausa/riprendi** raggiungibile da tastiera, necessario per il criterio WCAG 2.2.2 (livello A). Aggiunto anche il rispetto di `prefers-reduced-motion`.
- Quando le voci non superano quelle visibili insieme, il carosello diventa una riga statica centrata senza comandi, invece di mostrare frecce che non rispondono.
- Il titolo è collegato alla sezione con `aria-labelledby` invece di essere ripetuto in un `aria-label`.

### 1.0.1

- **Correzione importante all'aggiornatore.** `after_install()` non verificava che l'aggiornamento in corso riguardasse questo plugin: essendo `upgrader_post_install` un filtro globale, il codice interveniva sui file di *qualsiasi* plugin o tema aggiornato sul sito. Il file è ora allineato al componente condiviso `DB_GitHub_Updater`, che include il controllo su `$hook_extra['plugin']`, l'header `User-Agent` richiesto dall'API GitHub, la cache dei fallimenti e la riattivazione del plugin dopo l'aggiornamento.
- Pagina di dettaglio: i metadati degli allegati non generano più warning PHP né mostrano "0 B" quando il file non è più sul disco (spostato, rimosso o servito da CDN). L'estensione si ricava dall'URL, la dimensione viene omessa se non calcolabile.
- Carosello: rimosso l'`INNER JOIN` sulla postmeta introdotto da `meta_key` + `meta_value_num`, che escludeva le voci prive del campo *ordine* anche con il flag attivo. L'ordinamento avviene ora in PHP e le voci senza ordine valgono 0.
- Testo alternativo del logo: la validazione non usa più l'attributo HTML5 `required`, che dentro Gutenberg poteva bloccare il salvataggio senza mostrare l'errore. Al suo posto un avviso in bacheca dopo il salvataggio.
- Allegati: l'indice delle nuove righe usa un contatore progressivo invece di `Date.now()`, che poteva collidere per righe aggiunte nello stesso millisecondo.
- Allineato `db-admin-ui.css` al design system condiviso e corretti i riferimenti in `admin.css` (`--db-muted` non esiste nel design system, il token corretto è `--db-text-muted`).

### 1.0.0

- Prima versione pubblica.

## Note sul tema

Il carosello riusa la libreria **Splide** che il tema Design Scuole Italia carica già (`assets/js/splide.min.js`), ma la inizializza per conto proprio con opzioni esplicite. Non ci si appoggia all'inizializzatore del tema, per tre motivi emersi leggendone il codice:

1. `bootstrap-italia.js` cerca le classi `it-carousel-*` **sull'elemento Splide**, non sul wrapper, e se non le trova monta comunque con ogni opzione a `undefined`;
2. per i caroselli a tre colonne passa `arrows: false`, quindi le frecce del plugin non verrebbero mai collegate;
3. non prevede alcuna opzione di autoplay.

Nessuna libreria viene duplicata: se `window.Splide` non esiste — su un tema diverso, o se il tema cambia — il markup degrada a una lista statica di loghi, che resta perfettamente navigabile e accessibile.

Il tema espone anche un inizializzatore Owl Carousel per `.it-carousel-wrapper`, che si aspetta però un elemento `.owl-carousel.it-carousel-all`: è una strada diversa, non usata da questo plugin.
