<?php

declare(strict_types=1);

namespace app\models;

use app\components\AdminImageStorage;
use yii\base\Model;
use yii\web\UploadedFile;

final class CategoryForm extends Model
{
    public ?string $title = null;
    public $image = null;
    public bool $isNew = true;

    public function rules(): array
    {
        return [
            ['title', 'trim'],
            ['title', 'required', 'message' => 'Denumirea categoriei este obligatorie.'],
            ['title', 'string', 'max' => 255, 'tooLong' => 'Denumirea poate avea maximum 255 de caractere.'],
            ['image', 'file', 'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
                'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'], 'maxSize' => AdminImageStorage::MAX_BYTES,
                'skipOnEmpty' => true, 'wrongExtension' => 'Folosește o imagine JPG, PNG sau WebP.',
                'wrongMimeType' => 'Fișierul trebuie să fie o imagine JPG, PNG sau WebP.',
                'tooBig' => 'Imaginea poate avea maximum 8 MB.'],
            ['image', 'validateImage', 'skipOnEmpty' => false],
        ];
    }

    public function attributeLabels(): array
    {
        return ['title' => 'Denumire categorie', 'image' => 'Imagine categorie'];
    }

    public static function fromCategory(Category $category): self
    {
        $form = new self();
        $form->isNew = false;
        $form->title = $category->title;
        return $form;
    }

    public function validateImage(string $attribute): void
    {
        if ($this->isNew && $this->image === null) {
            $this->addError($attribute, 'Selectează o imagine pentru categorie.');
        } elseif ($this->image !== null && !AdminImageStorage::isValidImage($this->image)) {
            $this->addError($attribute, 'Folosește o imagine JPG, PNG sau WebP validă, de maximum 8 MB.');
        }
    }
}
