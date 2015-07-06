<?php

namespace Url;

use Doctrine\ORM\Mapping as ORM;
use Kdyby;

/**
 * @ORM\Entity
 * @ORM\Table(name="urls", indexes={
 *      @ORM\Index(columns={"fake_path"})
 * })
 *
 * @method setFakePath(string)
 * @method string getFakePath()
 * @method setDestination(string)
 * @method string getDestination()
 * @method setInternalId(integer)
 * @method integer getInternalId()
 */
class Url extends Kdyby\Doctrine\Entities\BaseEntity
{

	use Kdyby\Doctrine\Entities\Attributes\Identifier;

	/**
	 * @ORM\Column(type="string", unique=TRUE, nullable=TRUE, options={"comment":"Fake path of the URL"})
	 * @var string
	 */
	protected $fakePath;

	/**
	 * @ORM\Column(type="string", options={"comment":"Internal link destination (Module:Presenter:action)"})
	 * @var string
	 */
	protected $destination;

	/**
	 * @ORM\Column(type="integer", nullable=TRUE, options={"comment":"Internal ID passed to the action (optional)"})
	 * @var int
	 */
	protected $internalId = NULL;

	/**
	 * @ORM\OneToOne(targetEntity="Url", cascade={"persist"}, orphanRemoval=FALSE)
	 * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
	 * @var Url
	 */
	protected $parent;

	public function __construct()
	{
		$this->parent = $this->getId();
	}

}