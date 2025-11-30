<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrderArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderArticleRepository::class)]
#[ORM\Table(name: 'orders_article', options: ['charset' => 'utf8mb4', 'collate' => 'utf8mb4_unicode_ci'])]
#[ORM\Index(columns: ['article_id'], name: 'IDX_318C0B7C7294869C')]
#[ORM\Index(columns: ['orders_id'], name: 'IDX_318C0B7C7FC358ED')]
class OrderArticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(name: 'orders_id', referencedColumnName: 'id', nullable: true)]
    private ?Order $order = null;

    #[ORM\Column(name: 'article_id', type: Types::INTEGER, nullable: true)]
    private ?int $articleId = null;

    #[ORM\Column(type: Types::FLOAT)]
    private float $amount;

    #[ORM\Column(type: Types::FLOAT)]
    private float $price;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $priceEur = null;

    #[ORM\Column(type: Types::STRING, length: 3, nullable: true)]
    private ?string $currency = null;

    #[ORM\Column(type: Types::STRING, length: 2, nullable: true)]
    private ?string $measure = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deliveryTimeMin = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deliveryTimeMax = null;

    #[ORM\Column(type: Types::FLOAT)]
    private float $weight;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $multiplePallet = null;

    #[ORM\Column(type: Types::FLOAT)]
    private float $packagingCount;

    #[ORM\Column(type: Types::FLOAT)]
    private float $pallet;

    #[ORM\Column(type: Types::FLOAT)]
    private float $packaging;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => 0])]
    private bool $swimmingPool = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function getArticleId(): ?int
    {
        return $this->articleId;
    }

    public function setArticleId(?int $articleId): self
    {
        $this->articleId = $articleId;
        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getPriceEur(): ?float
    {
        return $this->priceEur;
    }

    public function setPriceEur(?float $priceEur): self
    {
        $this->priceEur = $priceEur;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getMeasure(): ?string
    {
        return $this->measure;
    }

    public function setMeasure(?string $measure): self
    {
        $this->measure = $measure;
        return $this;
    }

    public function getDeliveryTimeMin(): ?\DateTimeInterface
    {
        return $this->deliveryTimeMin;
    }

    public function setDeliveryTimeMin(?\DateTimeInterface $deliveryTimeMin): self
    {
        $this->deliveryTimeMin = $deliveryTimeMin;
        return $this;
    }

    public function getDeliveryTimeMax(): ?\DateTimeInterface
    {
        return $this->deliveryTimeMax;
    }

    public function setDeliveryTimeMax(?\DateTimeInterface $deliveryTimeMax): self
    {
        $this->deliveryTimeMax = $deliveryTimeMax;
        return $this;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): self
    {
        $this->weight = $weight;
        return $this;
    }

    public function getMultiplePallet(): ?int
    {
        return $this->multiplePallet;
    }

    public function setMultiplePallet(?int $multiplePallet): self
    {
        $this->multiplePallet = $multiplePallet;
        return $this;
    }

    public function getPackagingCount(): float
    {
        return $this->packagingCount;
    }

    public function setPackagingCount(float $packagingCount): self
    {
        $this->packagingCount = $packagingCount;
        return $this;
    }

    public function getPallet(): float
    {
        return $this->pallet;
    }

    public function setPallet(float $pallet): self
    {
        $this->pallet = $pallet;
        return $this;
    }

    public function getPackaging(): float
    {
        return $this->packaging;
    }

    public function setPackaging(float $packaging): self
    {
        $this->packaging = $packaging;
        return $this;
    }

    public function isSwimmingPool(): bool
    {
        return $this->swimmingPool;
    }

    public function setSwimmingPool(bool $swimmingPool): self
    {
        $this->swimmingPool = $swimmingPool;
        return $this;
    }
}
