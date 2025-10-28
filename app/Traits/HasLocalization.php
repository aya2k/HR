<?php

namespace App\Traits;

trait HasLocalization
{
    public function getNameAttribute()
    {
        return $this->{'name_' . app()->getLocale()};
    }

    public function getTitleAttribute()
    {
        return $this->{'title_' . app()->getLocale()};
    }

    public function getBodyAttribute()
    {
        return $this->{'body_' . app()->getLocale()};
    }

    public function getDescriptionAttribute()
    {
        return $this->{'description_' . app()->getLocale()};
    }

    public function getQuestionAttribute()
    {
        return $this->{'question_' . app()->getLocale()};
    }

    public function getTextAttribute()
    {
        return $this->{'text_' . app()->getLocale()};
    }

    public function getAnswerAttribute()
    {
        return $this->{'answer_' . app()->getLocale()};
    }

    
}