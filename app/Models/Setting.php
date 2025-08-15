<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    const TYPES = [
        'string' => 'string',
        'integer' => 'integer',
        'boolean' => 'boolean',
        'json' => 'json',
        'text' => 'text',
    ];

    const GROUPS = [
        'general' => 'general',
        'security' => 'security',
        'email' => 'email',
        'charter' => 'charter',
        'audit' => 'audit',
        'mfa' => 'mfa',
    ];

    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }
            
            return self::castValue($setting->value, $setting->type);
        });
    }

    public static function set(string $key, $value, string $type = 'string', string $group = 'general'): self
    {
        Cache::forget("setting_{$key}");
        
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => self::prepareValue($value, $type),
                'type' => $type,
                'group' => $group,
            ]
        );
    }

    public static function getByGroup(string $group): array
    {
        return Cache::remember("settings_group_{$group}", 3600, function () use ($group) {
            return self::where('group', $group)
                ->get()
                ->mapWithKeys(function ($setting) {
                    return [$setting->key => self::castValue($setting->value, $setting->type)];
                })
                ->toArray();
        });
    }

    public static function clearCache(): void
    {
        Cache::flush();
    }
    
    /**
     * Check if guest email validation is enabled
     */
    public static function isGuestEmailValidationEnabled(): bool
    {
        return (bool) self::get('guest_email_validation_enabled', true);
    }
    
    /**
     * Get guest validation delay in minutes
     */
    public static function getGuestValidationDelayMinutes(): int
    {
        return (int) self::get('guest_validation_delay_minutes', 30);
    }
    
    /**
     * Get charter text for a specific language
     */
    public static function getCharter(string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $key = "charter_text_{$locale}";
        
        $charter = self::get($key);
        
        if (!$charter) {
            // Return default charter if not found
            return self::getDefaultCharter($locale);
        }
        
        return $charter;
    }
    
    /**
     * Get charter text as HTML (parsed from Markdown)
     */
    public static function getCharterHtml(string $locale = null): string
    {
        $markdown = self::getCharter($locale);
        $markdownService = app(\App\Services\MarkdownService::class);
        
        return $markdownService->parseWithStyles($markdown);
    }
    
    /**
     * Get default charter text
     */
    protected static function getDefaultCharter(string $locale): string
    {
        $charters = [
            'fr' => "# CONDITIONS GÉNÉRALES D'UTILISATION DU RÉSEAU

## 1. OBJET
Cette charte définit les conditions d'utilisation du réseau informatique et d'accès à Internet.

## 2. ENGAGEMENTS DE L'UTILISATEUR
L'utilisateur s'engage à :
- Respecter la **législation en vigueur**
- Ne pas porter atteinte à l'intégrité ou à la sensibilité d'autrui
- Ne pas diffuser d'informations à caractère diffamatoire, injurieux, obscène ou offensant
- Respecter les **droits de propriété intellectuelle**
- Ne pas compromettre la sécurité du réseau

## 3. UTILISATION DU RÉSEAU
L'accès au réseau est **strictement personnel** et non cessible.
Toute tentative d'accès non autorisé est interdite.

## 4. SÉCURITÉ
L'utilisateur est responsable de la sécurité de ses identifiants.
Il s'engage à signaler toute anomalie constatée.

> **Important:** Ne partagez jamais vos identifiants avec des tiers.

## 5. RESPONSABILITÉ
L'établissement ne peut être tenu responsable de l'usage fait du réseau par l'utilisateur.

## 6. SANCTIONS
Le non-respect de cette charte peut entraîner la **suspension immédiate** de l'accès au réseau.

---

*En acceptant cette charte, vous reconnaissez avoir pris connaissance de ces conditions et vous engagez à les respecter.*",
            
            'en' => "# NETWORK TERMS OF USE

## 1. PURPOSE
This charter defines the conditions for using the computer network and Internet access.

## 2. USER COMMITMENTS
The user agrees to:
- Comply with **applicable laws**
- Not harm the integrity or sensitivity of others
- Not disseminate defamatory, insulting, obscene or offensive information
- Respect **intellectual property rights**
- Not compromise network security

## 3. NETWORK USE
Network access is **strictly personal** and non-transferable.
Any unauthorized access attempt is prohibited.

## 4. SECURITY
The user is responsible for the security of their credentials.
They agree to report any anomalies observed.

> **Important:** Never share your credentials with third parties.

## 5. LIABILITY
The establishment cannot be held responsible for the user's use of the network.

## 6. SANCTIONS
Failure to comply with this charter may result in **immediate suspension** of network access.

---

*By accepting this charter, you acknowledge having read these conditions and agree to comply with them.*",
            
            'it' => "# TERMINI DI UTILIZZO DELLA RETE

## 1. SCOPO
Questo regolamento definisce le condizioni di utilizzo della rete informatica e dell'accesso a Internet.

## 2. IMPEGNI DELL'UTENTE
L'utente si impegna a:
- Rispettare le **leggi vigenti**
- Non ledere l'integrità o la sensibilità altrui
- Non diffondere informazioni diffamatorie, ingiuriose, oscene o offensive
- Rispettare i **diritti di proprietà intellettuale**
- Non compromettere la sicurezza della rete

## 3. UTILIZZO DELLA RETE
L'accesso alla rete è **strettamente personale** e non cedibile.
Qualsiasi tentativo di accesso non autorizzato è vietato.

## 4. SICUREZZA
L'utente è responsabile della sicurezza delle proprie credenziali.
Si impegna a segnalare qualsiasi anomalia riscontrata.

> **Importante:** Non condividere mai le proprie credenziali con terzi.

## 5. RESPONSABILITÀ
L'istituzione non può essere ritenuta responsabile dell'uso della rete da parte dell'utente.

## 6. SANZIONI
Il mancato rispetto di questo regolamento può comportare la **sospensione immediata** dell'accesso alla rete.

---

*Accettando questo regolamento, riconosci di aver letto queste condizioni e ti impegni a rispettarle.*",
            
            'es' => "# TÉRMINOS DE USO DE LA RED

## 1. PROPÓSITO
Esta carta define las condiciones de uso de la red informática y el acceso a Internet.

## 2. COMPROMISOS DEL USUARIO
El usuario se compromete a:
- Cumplir con las **leyes aplicables**
- No dañar la integridad o sensibilidad de otros
- No difundir información difamatoria, insultante, obscena u ofensiva
- Respetar los **derechos de propiedad intelectual**
- No comprometer la seguridad de la red

## 3. USO DE LA RED
El acceso a la red es **estrictamente personal** e intransferible.
Cualquier intento de acceso no autorizado está prohibido.

## 4. SEGURIDAD
El usuario es responsable de la seguridad de sus credenciales.
Se compromete a informar cualquier anomalía observada.

> **Importante:** Nunca comparta sus credenciales con terceros.

## 5. RESPONSABILIDAD
El establecimiento no puede ser responsable del uso de la red por parte del usuario.

## 6. SANCIONES
El incumplimiento de esta carta puede resultar en la **suspensión inmediata** del acceso a la red.

---

*Al aceptar esta carta, reconoce haber leído estas condiciones y se compromete a cumplirlas.*",
        ];
        
        return $charters[$locale] ?? $charters['fr'];
    }

    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => (bool) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    protected static function prepareValue($value, string $type): string
    {
        return match ($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    protected static function boot()
    {
        parent::boot();
        
        static::saved(function ($setting) {
            Cache::forget("setting_{$setting->key}");
            Cache::forget("settings_group_{$setting->group}");
        });
        
        static::deleted(function ($setting) {
            Cache::forget("setting_{$setting->key}");
            Cache::forget("settings_group_{$setting->group}");
        });
    }
}