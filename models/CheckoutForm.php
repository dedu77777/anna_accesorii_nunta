<?php

namespace app\models;

use yii\base\Model;

class CheckoutForm extends Model
{
    public $full_name;
    public $email;
    public $phone_number;
    public $delivery_method;
    public $delivery_address;
    public $additional_information;
    public $privacy_accepted;
    public $idempotency_key;

    public function rules(): array
    {
        return [
            [['full_name', 'email', 'phone_number', 'delivery_method', 'privacy_accepted', 'idempotency_key'], 'required'],
            [['full_name'], 'string', 'min' => 2, 'max' => 120],
            [['email'], 'email'],
            [['email'], 'string', 'max' => 254],
            [['phone_number'], 'string', 'min' => 7, 'max' => 24],
            [['delivery_method'], 'in', 'range' => ['pickup', 'home_delivery']],
            [['delivery_address'], 'string', 'max' => 500],
            [['delivery_address'], 'required', 'when' => static fn ($model) => $model->delivery_method === 'home_delivery'],
            [['additional_information'], 'string', 'max' => 2000],
            [['privacy_accepted'], 'compare', 'compareValue' => '1'],
            [['idempotency_key'], 'match', 'pattern' => '/\A[a-f0-9]{64}\z/D'],
            [['full_name', 'email', 'phone_number', 'delivery_address', 'additional_information'], 'match', 'pattern' => '/\A[^\x00-\x08\x0B\x0C\x0E-\x1F\x7F]*\z/u', 'message' => 'Textul conține caractere nepermise.'],
            [['phone_number'], 'match', 'pattern' => '/\A\+?[0-9 ()-]{7,24}\z/D', 'message' => 'Numărul de telefon nu este valid.'],
            [['phone_number'], 'validatePhone'],
            [['full_name'], 'validateName'],
        ];
    }

    public function validatePhone(string $attribute): void
    {
        if (!is_string($this->$attribute)) {
            $this->addError($attribute, 'Numărul de telefon nu este valid.');
            return;
        }
        $digits = preg_replace('/\D/', '', (string) $this->$attribute);
        if (strlen($digits) < 7 || strlen($digits) > 15) {
            $this->addError($attribute, 'Numărul de telefon trebuie să conțină între 7 și 15 cifre.');
        }
    }

    public function validateName(string $attribute): void
    {
        if (!is_string($this->$attribute)) {
            $this->addError($attribute, 'Introdu un nume complet valid.');
            return;
        }
        if (mb_strlen(trim((string) $this->$attribute)) < 2) {
            $this->addError($attribute, 'Introdu un nume complet valid.');
        }
    }

    public function attributeLabels(): array
    {
        return [
            'full_name' => 'Nume complet', 'email' => 'Email', 'phone_number' => 'Telefon',
            'delivery_method' => 'Metoda de livrare', 'delivery_address' => 'Adresa de livrare',
            'additional_information' => 'Informații suplimentare', 'privacy_accepted' => 'Politica de confidențialitate',
        ];
    }
}
