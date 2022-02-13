<?php

namespace Pterodactyl\Models;

class Egg extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'egg';

    /**
     * Different features that can be enabled on any given egg. These are used internally
     * to determine which types of frontend functionality should be shown to the user. Eggs
     * will automatically inherit features from a parent egg if they are already configured
     * to copy configuration values from said egg.
     *
     * To skip copying the features, an empty array value should be passed in ("[]") rather
     * than leaving it null.
     */
    public const FEATURE_EULA_POPUP = 'eula';
    public const FEATURE_FASTDL = 'fastdl';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'eggs';

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nest_id',
        'uuid',
        'name',
        'description',
        'features',
        'author',
        'docker_images',
        'file_denylist',
        'config_files',
        'config_startup',
        'config_stop',
        'config_from',
        'startup',
        'script_is_privileged',
        'script_install',
        'script_entry',
        'script_container',
        'copy_script_from',
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'nest_id' => 'integer',
        'config_from' => 'integer',
        'script_is_privileged' => 'boolean',
        'copy_script_from' => 'integer',
        'features' => 'array',
        'docker_images' => 'array',
        'file_denylist' => 'array',
    ];

    public static array $validationRules = [
        'nest_id' => 'required|bail|numeric|exists:nests,id',
        'uuid' => 'required|string|size:36',
        'name' => 'required|string|max:191',
        'description' => 'string|nullable',
        'features' => 'array|nullable',
        'author' => 'required|string|email',
        'file_denylist' => 'array|nullable',
        'file_denylist.*' => 'string',
        'docker_images' => 'required|array|min:1',
        'docker_images.*' => 'required|string',
        'startup' => 'required|nullable|string',
        'config_from' => 'sometimes|bail|nullable|numeric|exists:eggs,id',
        'config_stop' => 'required_without:config_from|nullable|string|max:191',
        'config_startup' => 'required_without:config_from|nullable|json',
        'config_files' => 'required_without:config_from|nullable|json',
        'update_url' => 'sometimes|nullable|string',
    ];

    /**
     * @var array
     */
    protected $attributes = [
        'features' => null,
        'file_denylist' => null,
        'config_stop' => null,
        'config_startup' => null,
        'config_files' => null,
        'update_url' => null,
    ];

    /**
     * Returns the install script for the egg; if egg is copying from another
     * it will return the copied script.
     *
     * @return string
     */
    public function getCopyScriptInstallAttribute()
    {
        if (!is_null($this->script_install) || is_null($this->copy_script_from)) {
            return $this->script_install;
        }

        return $this->scriptFrom->script_install;
    }

    /**
     * Returns the entry command for the egg; if egg is copying from another
     * it will return the copied entry command.
     *
     * @return string
     */
    public function getCopyScriptEntryAttribute()
    {
        // @phpstan-ignore-next-line
        if (!is_null($this->script_entry) || is_null($this->copy_script_from)) {
            return $this->script_entry;
        }

        // @phpstan-ignore-next-line
        return $this->scriptFrom->script_entry;
    }

    /**
     * Returns the install container for the egg; if egg is copying from another
     * it will return the copied install container.
     *
     * @return string
     */
    public function getCopyScriptContainerAttribute()
    {
        // @phpstan-ignore-next-line
        if (!is_null($this->script_container) || is_null($this->copy_script_from)) {
            return $this->script_container;
        }

        // @phpstan-ignore-next-line
        return $this->scriptFrom->script_container;
    }

    /**
     * Return the file configuration for an egg.
     *
     * @return string
     */
    public function getInheritConfigFilesAttribute()
    {
        if (!is_null($this->config_files) || is_null($this->config_from)) {
            return $this->config_files;
        }

        return $this->configFrom->config_files;
    }

    /**
     * Return the startup configuration for an egg.
     *
     * @return string
     */
    public function getInheritConfigStartupAttribute()
    {
        if (!is_null($this->config_startup) || is_null($this->config_from)) {
            return $this->config_startup;
        }

        return $this->configFrom->config_startup;
    }

    /**
     * Return the stop command configuration for an egg.
     *
     * @return string
     */
    public function getInheritConfigStopAttribute()
    {
        if (!is_null($this->config_stop) || is_null($this->config_from)) {
            return $this->config_stop;
        }

        return $this->configFrom->config_stop;
    }

    /**
     * Returns the features available to this egg from the parent configuration if there are
     * no features defined for this egg specifically and there is a parent egg configured.
     *
     * @return array|null
     */
    public function getInheritFeaturesAttribute()
    {
        if (!is_null($this->features) || is_null($this->config_from)) {
            return $this->features;
        }

        return $this->configFrom->features;
    }

    /**
     * Returns the features available to this egg from the parent configuration if there are
     * no features defined for this egg specifically and there is a parent egg configured.
     *
     * @return string[]|null
     */
    public function getInheritFileDenylistAttribute()
    {
        if (is_null($this->config_from)) {
            return $this->file_denylist;
        }

        return $this->configFrom->file_denylist;
    }

    /**
     * Gets nest associated with an egg.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function nest()
    {
        return $this->belongsTo(Nest::class);
    }

    /**
     * Gets all servers associated with this egg.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function servers()
    {
        return $this->hasMany(Server::class, 'egg_id');
    }

    /**
     * Gets all variables associated with this egg.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variables()
    {
        return $this->hasMany(EggVariable::class, 'egg_id');
    }

    /**
     * Get the parent egg from which to copy scripts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function scriptFrom()
    {
        return $this->belongsTo(self::class, 'copy_script_from');
    }

    /**
     * Get the parent egg from which to copy configuration settings.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function configFrom()
    {
        return $this->belongsTo(self::class, 'config_from');
    }
}
