<?php

namespace App\Entity;

use App\Repository\StockLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use apiplatform\metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete()
    ],
    normalizationContext: [
        'groups' => ['stock_log:read']
    ],
    denormalizationContext: [
        'groups' => ['stock_log:write']
    ]
)]

#[ORM\Entity(repositoryClass: StockLogRepository::class)]
#[ORM\Table(name: 'stock_log')]
class StockLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['stock_log:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['stock_log:read', 'stock_log:write'])]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['stock_log:read', 'stock_log:write'])]
    private ?User $user = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Groups(['stock_log:read'])]
    private ?string $username = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['stock_log:read'])]
    private ?string $role = null;

    #[ORM\Column(length: 50)]
    #[Groups(['stock_log:read'])]
    private string $action;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['stock_log:read', 'stock_log:write'])]
    private ?int $quantityBefore = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['stock_log:read', 'stock_log:write'])]
    private ?int $quantityAfter = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['stock_log:read', 'stock_log:write'])]
    private ?int $changeAmount = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['stock_log:read', 'stock_log:write'])]
    private ?string $note = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['stock_log:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getQuantityBefore(): ?int
    {
        return $this->quantityBefore;
    }

    public function setQuantityBefore(?int $quantityBefore): static
    {
        $this->quantityBefore = $quantityBefore;

        return $this;
    }

    public function getQuantityAfter(): ?int
    {
        return $this->quantityAfter;
    }

    public function setQuantityAfter(?int $quantityAfter): static
    {
        $this->quantityAfter = $quantityAfter;

        return $this;
    }

    public function getChangeAmount(): ?int
    {
        return $this->changeAmount;
    }

    public function setChangeAmount(?int $changeAmount): static
    {
        $this->changeAmount = $changeAmount;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
