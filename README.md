# GamiPress Bridge

Un plugin leggero per WordPress che espone endpoint REST API sicuri e ottimizzati per integrare un'applicazione mobile nativa o web con il sistema di gamification **GamiPress**.

## 📌 Funzionalità
Il plugin crea quattro endpoint per gestire gli aggiornamenti a Gamipress:
- **POST** /wp-json/app-gamipress/v1/assegna_punti
- **POST** /wp-json/app-gamipress/v1/assegna_badge
- **POST** /wp-json/app-gamipress/v1/assegna_achievement
- **GET** /wp-json/app-gamipress/v1/profilo_me

**Profilo Utente Unificato (`GET`):** Recupera in un'unica chiamata leggera punti, rank, badge sbloccati e storie/casi completati dall'utente autenticato.
**Assegnazione Punti (`POST`):** Premia il giocatore con punti esperienza/detective.
**Assegnazione Badge (`POST`):** Rilascia badge specifici per abilità o traguardi.
**Assegnazione Achievement / Casi Chiusi (`POST`):** Registra il completamento di singole storie o investigazioni.

**Sicurezza Integrata:** Tutte le chiamate utilizzano il contesto dell'utente loggato via Token HTTP, impedendo la manipolazione dei dati da parte di utenti non autorizzati o terzi.

## 🛠️ Requisiti
- WordPress 5.8 o superiore
- Plugin [GamiPress](https://wordpress.org/plugins/gamipress/) attivo
- Sistema di autenticazione API attivo su WordPress (es. **JWT Authentication for WP REST API** oppure **Application Passwords**)

## 📦 Installazione
1. Scarica la cartella `app-gamipress-bridge` o crea un file `app-gamipress-bridge.php`.
2. Inserisci la cartella all'interno della directory dei plugin: `/wp-content/plugins/`.
3. Accedi al pannello di amministrazione di WordPress > **Plugin**.
4. Trova **App GamiPress Bridge** e clicca su **Attiva**.

## 🔑 Autenticazione
Ogni richiesta inviata dall'app verso gli endpoint descritti di seguito **deve contenere** l'header HTTP di autorizzazione con il token dell'utente:

```
http
Authorization: Bearer <TOKEN_JWT_O_APP_PASSWORD>
Content-Type: application/json`

{
  "success": true,
  "user_id": 12,
  "user_display_name": "Mario Rossi",
  "punti": {
    "detective": 350
  },
  "rank": {
    "id": 105,
    "title": "Detective Capo"
  },
  "badge": [
    {
      "id": 456,
      "title": "Esperto dell'occulto",
      "date": "2026-09-20 14:30:00"
    }
  ],
  "casi_chiusi": [
    {
      "id": 789,
      "title": "Il mistero della villa disabitata",
      "date": "2026-09-22 18:00:00"
    }
  ]
}
```