<?php

namespace App\Model;

use Assert\Assert;

/**
 * Class User
 *
 * @package App\Model
 */
class User implements \Serializable
{

    /**
     * @var integer|null
     */
    private $id;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $lastName;

    /**
     * User constructor.
     *
     * @param string $username      Username used for authentication.
     * @param string $plainPassword Plain password.
     * @param string $firstName     First name.
     * @param string $lastName      Last name.
     */
    public function __construct(
        string $username,
        string $plainPassword,
        string $firstName,
        string $lastName
    ) {
        Assert::lazy()
            ->that($username, 'username')->notBlank()->maxLength(255)
            ->that($plainPassword, 'plainPassword')->minLength(6)->maxLength(255)
            ->that($firstName, 'firstName')->notBlank()->maxLength(255)
            ->that($lastName, 'lastName')->notBlank()->maxLength(255)
            ->tryAll();

        $this->username = $username;
        $this->password = password_hash($plainPassword, PASSWORD_BCRYPT);
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    /**
     * @return integer|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username Username used for authentication.
     *
     * @return User
     */
    public function setUsername(string $username): User
    {
        Assert::that($username)->notBlank()->maxLength(255);
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $plainPassword Plain password.
     *
     * @return User
     */
    public function changePassword(string $plainPassword): User
    {
        Assert::that($plainPassword)->minLength(6)->maxLength(255);
        $this->password = password_hash($plainPassword, PASSWORD_BCRYPT);

        return $this;
    }

    /**
     * Check that specified password is valid for this user.
     *
     * @param string $checkedPassword Checked password.
     *
     * @return boolean
     */
    public function isValidPassword(string $checkedPassword): bool
    {
        return password_verify($checkedPassword, $this->password);
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName First name.
     *
     * @return User
     */
    public function setFirstName(string $firstName): User
    {
        Assert::that($firstName)->notBlank()->maxLength(255);
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName Last name.
     *
     * @return User
     */
    public function setLastName(string $lastName): User
    {
        Assert::that($lastName)->notBlank()->maxLength(255);
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * String representation of object.
     *
     * @return string the string representation of the object or null
     */
    public function serialize(): string
    {
        return serialize([
            $this->id,
            $this->username,
            $this->firstName,
            $this->lastName,
        ]);
    }

    /**
     * Constructs the object.
     *
     * @param string $serialized The string representation of the object.
     *
     * @return void
     */
    public function unserialize($serialized) // @codingStandardsIgnoreLine
    {
        $data = unserialize($serialized, [ 'allowed_classes' => static::class ]);

        if (count($data) !== 4) {
            throw new \RuntimeException(sprintf(
                'Can\'t unserialize model \'%s\'. Serialized data: \'%s\'',
                static::class,
                $serialized
            ));
        }

        $this->id = $data[0];
        $this->username = $data[1];
        $this->firstName = $data[2];
        $this->lastName = $data[3];
    }
}
