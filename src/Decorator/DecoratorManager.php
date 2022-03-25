<?php

namespace src\Decorator;

use src\Integration\DataProvider;
use DateTime;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * class:  DecoratorManager
 */
class DecoratorManager extends DataProvider
{
	// меняем видимость с public на private, что бы никто случайно "из вне" ничего не поломал
	private CacheItemPoolInterface $cache;
	private LoggerInterface $logger;

	/**
	 * @param string $host
	 * @param string $user
	 * @param string $password
	 * @param CacheItemPoolInterface $cache
	 * @param LoggerInterface $logger
	 * для переменных $host, $user, $password назначаем типы, что бы не прилетело чего случайно лишнего
	 * Много параметров уходит в конструктор, может привести к сложному рефакторингу в будущем.
	 * Завернуть $host, $user, $password в некий requestInputDTO и его прокидывать было бы более удобным решением
	 */
	public function __construct(string $host, string $user, string $password, CacheItemPoolInterface $cache, LoggerInterface $logger)
	{
		// Вот здесь не уверен, что верная логика. Анализ провести можно, зная логику класса DataProvider
		// Один из вариантов, это создание объекта DataProvider внутри DecoratorManager при необходиости получения свежих данных
		parent::__construct($host, $user, $password);

		$this->cache = $cache;
		$this->logger = $logger;
	}

	//  Выносим прокидывание логера в конструктор, там ему самое место
	//	public function setLogger(LoggerInterface $logger)
	//	{
	//		$this->logger = $logger;
	//	}

	/**
	 * {@inheritdoc}
	 * если приходящие данные $input валидируются и структурируются ранее, то гуд
	 * если бы приходил DTO с данными, я бы ему доверял больше, не зная остального кода. А так не ясно в каком виде пришел массив.
	 */
	public function getResponse(array $input)
	{
		try {
			// здесь логику работы с кешем не знаю, допустим, что получение данных и проверка актуальности происходит именно так
			$cacheKey = $this->getCacheKey($input);
			$cacheItem = $this->cache->getItem($cacheKey);
			if ($cacheItem->isHit()) {
				return $cacheItem->get();
			}

			// заменил "parent::" на "$this->" и переименовал метод на более "понимательный"
			// нет проверки на $result, возможно она заложена в DataProvider и при невалидных ответах отдает какой либо Exception
			//$result = parent::get($input);
			$result = $this->getResponseProvider($input);

			$cacheItem
				->set($result)
				->expiresAt((new DateTime())->modify('+1 day'));

			return $result;
		} catch (Exception $e) {
			// здесь нет данных по ошибке отправляемых в логер, например $e->getMessage() и $e->getTrace() массивом или строкой.
			// возможно могут потребоваться данные о родителе $e->getPrevious(), если таковой есть
			$this->logger->critical('Error');
			// вероятно требуется прокинуть Exception далее, сложно что либо сказать, не видя картины целиком
		}

		// возможно это корректный ответ, если таковой регламентирован при ошибках
		return [];
	}

	public function getCacheKey(array $input)
	{
		// оборачиваем в md5, для сокращения строки и оптимизации расхода памяти кеша
		// return json_encode($input);
		return md5(json_encode($input));
	}
}
