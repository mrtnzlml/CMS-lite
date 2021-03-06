<?php

namespace Dashboard\Presenters;

use App\AdminModule\Presenters\BasePresenter;
use App\Components\Flashes\Flashes;
use Dashboard\Components\IAdvertisementFactory;
use Error\Components\ErrorGrid\IErrorGridFactory;
use Eshop\Components\ILastOrdersFactory;
use Notes\Components\INotesFactory;

class DashboardPresenter extends BasePresenter
{

	public function createComponentNotes(INotesFactory $factory)
	{
		$control = $factory->create($this->user->getId());
		$control->onDelete[] = function () {
			$this->flashMessage('Poznámka byla úspěšně smazána.', Flashes::FLASH_SUCCESS);
			$this->redirect('this');
		};
		return $control;
	}

	public function createComponentLastOrders(ILastOrdersFactory $factory)
	{
		$control = $factory->create();
		return $control;
	}

	protected function createComponentErrorGrid(IErrorGridFactory $factory)
	{
		$control = $factory->create();
		return $control;
	}

	protected function createComponentAdvertisement(IAdvertisementFactory $factory)
	{
		$control = $factory->create();
		return $control;
	}

}
