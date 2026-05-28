# Timebox – Deployment via GitHub → Metanet/Plesk

## Dateien im Projekt
```
index.php       ← Timer-Seite
admin.php       ← Einstellungsmenü (/admin.php)
.htaccess       ← Sicherheit (schützt settings.json)
settings.json   ← wird automatisch erstellt beim ersten Speichern
uploads/        ← wird automatisch erstellt für Logos
```

---

## Einmalig: GitHub-Repository erstellen

1. Geh auf **github.com** → **New repository**
2. Name z.B. `timebox`
3. Auf **Private** stellen
4. Repository erstellen

Dann lokal (oder via GitHub Desktop):
```bash
git init
git add .
git commit -m "Initial commit"
git remote add origin git@github.com:DEIN-USERNAME/timebox.git
git push -u origin main
```

---

## Einmalig: Plesk mit GitHub verbinden

Genau gleich wie bei vzev:

1. In Plesk: **Git** → **Repository hinzufügen**
2. Repository-URL: `https://github.com/DEIN-USERNAME/timebox.git`
3. Branch: `main`
4. Zielverzeichnis: das Verzeichnis deiner Subdomain (z.B. `timebox.deineseite.ch`)
5. **Deployment-Hook aktivieren** → Plesk gibt dir eine Webhook-URL

6. Diese Webhook-URL in GitHub eintragen:
   - GitHub → Repository → **Settings → Webhooks → Add webhook**
   - Payload URL: die URL von Plesk
   - Content type: `application/json`
   - Event: **Just the push event**
   - Speichern

Ab jetzt: **Jeder Push auf GitHub → Plesk deployed automatisch** ✓

---

## Subdomain einrichten in Plesk

1. Plesk → **Websites & Domains → Subdomain hinzufügen**
2. Subdomain: z.B. `timebox`
3. Dokumentenstamm auf den Ordner zeigen lassen, wo das Repo liegt

---

## Admin-Panel

Erreichbar unter:
```
https://timebox.deineseite.ch/admin.php
```

Einstellungen:
- Titel & Logo
- Hintergrund-, Text-, Akzent-, Warn- und Fertig-Farbe
- Preset-Zeiten
- Standard-Ton
- Passwortschutz für Admin

---

## Sicherheits-Hinweis

Setze im Admin-Panel unter **Sicherheit** ein Passwort,
damit nicht jeder die Einstellungen ändern kann.
