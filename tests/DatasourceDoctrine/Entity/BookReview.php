<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceDoctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'book_review')]
class BookReview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookReviews')]
    private ?Review $review = null;

    #[ORM\ManyToOne(inversedBy: 'bookReviews')]
    private ?Book $book = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReview(): ?Review
    {
        return $this->review;
    }

    public function setReview(?Review $review): static
    {
        $this->review = $review;

        return $this;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): static
    {
        $this->book = $book;

        return $this;
    }
}
