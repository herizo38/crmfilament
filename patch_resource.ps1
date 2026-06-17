$file = "C:\laragon\www\crmfilament\app\Filament\NsConseil\Resources\PartenaireResource.php"
$content = Get-Content $file -Raw

# Correcting both filters with proper Builder typing
$rdvOld = '->query(
        fn($query) => $query
            ->where(\'statut\', OrganizationStatus::RdvEnCours->value)
            ->where(\'date_modification_statut\', \'<\', now()->subDays(90))
    )'
$rdvNew = '->query(
        fn (Builder $query): Builder => $query
            ->where(\'statut\', OrganizationStatus::RdvEnCours->value)
            ->where(\'date_modification_statut\', \'<\', now()->subDays(90))
    )'

$convOld = '->query(fn($q) => $q->whereIn(\'statut\', ['
$convNew = '->query(fn (Builder $query): Builder => $query->whereIn(\'statut\', ['

$content = $content.Replace($rdvOld, $rdvNew)
$content = $content.Replace($convOld, $convNew)

Set-Content $file $content -Encoding UTF8

Write-Host "File patched successfully."
