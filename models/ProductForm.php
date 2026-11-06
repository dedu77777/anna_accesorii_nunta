<?php

declare(strict_types=1);

namespace app\models;

use app\components\AdminImageStorage;
use yii\base\Model;
use yii\web\UploadedFile;

final class ProductForm extends Model
{
    public ?string $title = null;
    public ?string $subtitle = null;
    public ?string $description = null;
    public $price = null;
    public $category_id = null;
    /** @var UploadedFile[] */
    public $images = [];
    public $removeImageIds = [];
    public array $existingImageIds = [];
    public bool $hasLegacyImage = false;
    public $removeLegacyImage = false;
    public bool $isNew = true;

    public function rules(): array
    {
        return [
            [['title', 'subtitle', 'description'], 'trim'],
            [['title', 'description', 'price', 'category_id'], 'required', 'message' => 'Acest câmp este obligatoriu.'],
            [['title', 'subtitle'], 'string', 'max' => 255, 'tooLong' => 'Textul poate avea maximum 255 de caractere.'],
            ['description', 'string'],
            ['price', 'number', 'min' => 0.01, 'max' => 99999999.99,
                'message' => 'Introdu un preț valid.', 'tooSmall' => 'Prețul trebuie să fie mai mare decât zero.',
                'tooBig' => 'Prețul este prea mare.'],
            ['price', 'match', 'pattern' => '/^\d{1,8}(?:\.\d{1,2})?$/', 'message' => 'Introdu un preț cu cel mult două zecimale.'],
            ['category_id', 'integer', 'message' => 'Selectează o categorie validă.'],
            ['category_id', 'exist', 'targetClass' => Category::class, 'targetAttribute' => ['category_id' => 'id'],
                'message' => 'Categoria selectată nu există.'],
            ['removeImageIds', 'each', 'rule' => ['integer', 'min' => 1]],
            ['removeLegacyImage', 'boolean'],
            ['images', 'file', 'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
                'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'], 'maxSize' => AdminImageStorage::MAX_BYTES,
                'maxFiles' => 3, 'skipOnEmpty' => true,
                'wrongExtension' => 'Folosește imagini JPG, PNG sau WebP.',
                'wrongMimeType' => 'Fișierul trebuie să fie o imagine JPG, PNG sau WebP.',
                'tooBig' => 'Fiecare imagine poate avea maximum 8 MB.',
                'tooMany' => 'Poți încărca maximum 3 imagini.'],
            ['images', 'validateImageSelection', 'skipOnEmpty' => false],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'title' => 'Titlu produs',
            'subtitle' => 'Subtitlu',
            'description' => 'Descriere',
            'price' => 'Preț (lei)',
            'category_id' => 'Categorie',
            'images' => 'Imagini produs',
        ];
    }

    public static function fromProduct(Products $product): self
    {
        $form = new self();
        $form->isNew = false;
        $form->title = $product->title;
        $form->subtitle = $product->subtitle;
        $form->description = $product->description;
        $form->price = $product->price;
        $form->category_id = $product->category_id;
        $form->existingImageIds = array_map(static fn (ProductImages $image): int => (int) $image->id, $product->images);
        $form->hasLegacyImage = !$product->images && (bool) $product->image;
        return $form;
    }

    public function validateImageSelection(string $attribute): void
    {
        if (!is_array($this->removeImageIds)) {
            $this->addError('removeImageIds', 'Selecția imaginilor este invalidă.');
            return;
        }
        $removed = array_map('intval', $this->removeImageIds);
        if (count($removed) !== count(array_unique($removed)) || array_diff($removed, $this->existingImageIds)) {
            $this->addError('removeImageIds', 'Selecția imaginilor este invalidă.');
            return;
        }
        $remaining = count($this->existingImageIds) - count($removed) + (int) ($this->hasLegacyImage && !$this->removeLegacyImage) + count($this->images);
        if ($remaining < 1 || $remaining > 3) {
            $this->addError($attribute, 'Produsul trebuie să aibă între 1 și 3 imagini.');
        }
        foreach ($this->images as $image) {
            if (!$image instanceof UploadedFile || !AdminImageStorage::isValidImage($image)) {
                $this->addError($attribute, 'Folosește imagini JPG, PNG sau WebP valide, de maximum 8 MB.');
                break;
            }
        }
    }
}
