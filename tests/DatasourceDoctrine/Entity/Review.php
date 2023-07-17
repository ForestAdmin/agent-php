<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceDoctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'reviews')]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $author = null;

    #[ORM\Column]
    private ?int $rating = null;

    #[ORM\OneToMany(mappedBy: 'review', targetEntity: BookReview::class)]
    private Collection $bookReviews;

    public function __construct()
    {
        $this->bookReviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    /**
     * @return Collection<int, BookReview>
     */
    public function getBookReviews(): Collection
    {
        return $this->bookReviews;
    }

    public function addBookReview(BookReview $bookReview): static
    {
        if (! $this->bookReviews->contains($bookReview)) {
            $this->bookReviews->add($bookReview);
            $bookReview->setReview($this);
        }

        return $this;
    }

    public function removeBookReview(BookReview $bookReview): static
    {
        if ($this->bookReviews->removeElement($bookReview)) {
            // set the owning side to null (unless already changed)
            if ($bookReview->getReview() === $this) {
                $bookReview->setReview(null);
            }
        }

        return $this;
    }
}
