<?php

class Upload extends BaseModel {

	protected $guarded = array();

	protected $table = 'uploads';

	public static $rules = array();

    public function fullpath() {
        return str_replace('//', '/', Config::get('site.uploads_dir', public_path('uploads/files')) . "/" . basename($this->path));
    }
}