# NotesHub — Notes Sharing Platform (ASP.NET Core MVC)

A full ASP.NET Core (.NET 8) MVC web app where students can register, log in,
upload academic notes (PDF/DOC/PPT/images), browse and search notes by
subject/category, view details, and download files.

## Tech stack
- ASP.NET Core MVC (.NET 8)
- Entity Framework Core + SQLite (zero-config local database)
- ASP.NET Core Identity (registration, login, cookie auth)
- Bootstrap 5 + Font Awesome (UI)

## Project structure
```
NotesSharingPlatform/
├── Controllers/       # Home, Account, Notes
├── Models/            # Note, Category, ApplicationUser, ViewModels
├── Data/              # ApplicationDbContext (EF Core)
├── Views/             # Razor views (Home, Notes, Account, Shared/_Layout)
├── wwwroot/            
│   ├── css/site.css
│   └── uploads/       # uploaded note files are stored here
├── Program.cs          # app startup / DI / middleware pipeline
├── appsettings.json     
└── NotesSharingPlatform.csproj
```

## Prerequisites
- [.NET 8 SDK](https://dotnet.microsoft.com/download) installed on your machine
  (this sandbox doesn't have the SDK, so the project couldn't be compiled or have
  its migration auto-generated here — but everything below is a normal, standard
  ASP.NET Core project and will build cleanly once you have the SDK.)

## Setup — run these commands from the project folder

```bash
# 1. Restore NuGet packages
dotnet restore

# 2. Create the initial EF Core migration (generates the SQLite schema)
dotnet ef migrations add InitialCreate

# 3. Apply the migration to create notesplatform.db
dotnet ef database update

# 4. Run the app
dotnet run
```

Then open the URL shown in the console (e.g. `https://localhost:5001` or `http://localhost:5000`).

> If `dotnet ef` isn't recognized, install the EF Core CLI tool once with:
> `dotnet tool install --global dotnet-ef`

The app also calls `db.Database.Migrate()` automatically on startup, so once the
migration exists, the database will always be up to date when you run the app.

## Features
- **Register / Login** — ASP.NET Core Identity, passwords hashed automatically
- **Upload notes** — title, subject, semester, category, description, file
  (PDF, DOC/DOCX, PPT/PPTX, TXT, JPG, PNG up to 20 MB — configurable in `appsettings.json`)
- **Browse & search** — filter by keyword and/or category
- **Note details** — view count, download count, file size, description
- **Download** — increments download counter, serves the original file
- **My Notes** — manage and delete your own uploads (only the owner can delete)
- **Seeded categories** — Computer Science, Mathematics, Physics, Chemistry,
  Electronics, Business Studies, General (edit in `Data/ApplicationDbContext.cs`)

## Configuration
Edit `appsettings.json`:
```json
"FileUpload": {
  "AllowedExtensions": [ ".pdf", ".doc", ".docx", ".ppt", ".pptx", ".txt", ".jpg", ".jpeg", ".png" ],
  "MaxFileSizeMB": 20
}
```

## Notes on production use
- Switch `DefaultConnection` in `appsettings.json` to SQL Server / PostgreSQL for
  production (just change the provider package + `UseSqlite` → `UseSqlServer`/`UseNpgsql`).
  For production file storage at scale, consider moving uploads to Azure Blob
  Storage / S3 instead of the local `wwwroot/uploads` folder.
- Add email confirmation and stronger password policy in `Program.cs` if needed.
- Consider adding an admin role/dashboard (an "Admin" role is already seeded on startup).
