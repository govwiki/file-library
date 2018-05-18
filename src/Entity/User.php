<?php

namespace App\Entity;

use Assert\Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class User
 *
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 *
 * @package App\Entity
 */
class User implements \Serializable, \JsonSerializable
{

    /**
     * @var string
     *
     * @ORM\Column
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column
     */
    private $lastName;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $superUser;

    /**
     * User constructor.
     *
     * @param string  $username  Username used for authentication.
     * @param string  $password  Plain password.
     * @param string  $firstName First name.
     * @param string  $lastName  Last name.
     * @param boolean $superUser Super user flag.
     */
    public function __construct(
        string $username,
        string $password,
        string $firstName,
        string $lastName,
        bool $superUser = false
    ) {
        /** @psalm-suppress MixedMethodCall */
        Assert::lazy()
            ->that($username, 'username')->notBlank()->maxLength(255)
            ->that($password, 'password')->minLength(6)->maxLength(255)
            ->that($firstName, 'firstName')->notBlank()->maxLength(255)
            ->that($lastName, 'lastName')->notBlank()->maxLength(255)
            ->tryAll();

        $this->username = $username;
        $this->password = $password;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->superUser = $superUser;
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
        /** @psalm-suppress MixedMethodCall */
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
        /** @psalm-suppress MixedMethodCall */
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
        /** @psalm-suppress MixedMethodCall */
        Assert::that($firstName)->maxLength(255);
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
        /** @psalm-suppress MixedMethodCall */
        Assert::that($lastName)->maxLength(255);
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFullName(): string
    {
        return $this->firstName .' '. $this->lastName;
    }

    /**
     * @return boolean
     */
    public function isSuperUser(): bool
    {
        return $this->superUser;
    }

    /**
     * @param boolean $superUser Super user flag.
     *
     * @return $this
     */
    public function setSuperUser(bool $superUser)
    {
        $this->superUser = $superUser;

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
        /** @psalm-suppress MixedAssignment */
        $data = unserialize($serialized, [ 'allowed_classes' => [ static::class ] ]);

        if (! is_array($data) || (count($data) !== 4)) {
            throw new \RuntimeException(sprintf(
                'Can\'t unserialize model \'%s\'. Serialized data: \'%s\'',
                static::class,
                $serialized
            ));
        }

        /** @psalm-suppress MixedArrayAccess */
        $this->username = (string) $data[0];
        /** @psalm-suppress MixedArrayAccess */
        $this->firstName = (string) $data[1];
        /** @psalm-suppress MixedArrayAccess */
        $this->lastName = (string) $data[2];
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'username' => $this->username,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'isSuperUser' => $this->superUser,
        ];
    }
}
