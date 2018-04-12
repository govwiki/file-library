<?php

namespace App\Controller;

/**
 * Class ApiHttpException
 *
 * @package App\Controller
 */
class ApiHttpException extends \DomainException
{

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $errorCode;

    /**
     * @var string
     */
    private $description;

    /**
     * @var int
     */
    private $status;

    /**
     * ApiHttpException constructor.
     *
     * @param string  $title       Error title.
     * @param string  $errorCode   Application error code.
     * @param string  $description Error description.
     * @param integer $status      HTTP status code.
     */
    public function __construct(
        string $title,
        string $errorCode,
        string $description,
        int $status = 400
    ) {
        parent::__construct($title .': ' . $description);

        $this->title = $title;
        $this->errorCode = $errorCode;
        $this->description = $description;
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return integer
     */
    public function getStatus(): int
    {
        return $this->status;
    }
}
