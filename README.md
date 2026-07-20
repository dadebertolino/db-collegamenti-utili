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
| `per_view` | `3` | Loghi visibili per slide su desktop (1–4). |
| `autoplay` | `false` | Scorrimento automatico. Off di default per rispetto WCAG. |

Esempio:

```
[db_collegamenti_utili titolo="COLLEGAMENTI UTILI" per_view="3"]
```

Nel carosello compaiono **solo** le voci con il flag "Mostra nel carosello" attivo, ordinate per il campo *ordine*.

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

## Note sul tema

Il carosello si appoggia allo **Splide** caricato dal tema Design Scuola Italia (classe `it-carousel-wrapper`). Su un tema diverso il markup resta valido ma potrebbe non animarsi: in quel caso serve una variante autonoma del carosello.
