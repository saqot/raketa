<?php

namespace src\Integration;

/**
 * class:  DataProvider
 */
class DataProvider
{
	private string $host;
	private string $user;
	private string $password;

	/**
	 * @param string $host
	 * @param string $user
	 * @param string $password
	 * для переменных $host, $user, $password назначаем типы, что бы не прилетело чего случайно лишнего
	 */
	public function __construct(string $host, string $user, string $password)
	{
		$this->host = $host;
		$this->user = $user;
		$this->password = $password;
	}

	/**
	 * @param array $request
	 *
	 * @return array
	 */
	public function getResponseProvider(array $request)
	{
		// returns a response from external service
	}

}
