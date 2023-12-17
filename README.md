# Introduction

[Русская версия](#Введение)

The plugin enables a payment method within Moodle, allowing integration with payment services from providers operating on the beGateway platform.

# Installation

  * Download the beGateway Plugin for Moodle directly from [Github releases page](https://github.com/begateway/moodle-payment-plugin/releases)
  * Login to your Moodle Administrator page
  * Navigate to Site Administration -> Plugins -> Install plugins
  * Click 'Choose a file' -> 'Upload a file' -> 'Choose file' and select the plugin zip file and confirm
  * Click 'Show more...' and select 'Enrolment method (enrol)' as the Plugin type and confirm
  * Click 'Continue' after plugin validation completes
  * Click 'Continue' once more in the next page
  * Click 'Upgrade Moodle database now', then 'Continue' in the following page
  * Installation is now complete. Follow the next section to complete setup.

# Plugin Settings

  * Navigate to Site administration -> Plugins -> Plugins overview -> beGateway > Settings (if settings are not automatically shown)
  * Enter your Shop ID
  * Enter your Shop secret key
  * Enter your payment provider checkout domain
  * Enter your payment provider gateway domain
  * Enter your payment provider API domain
  * Enable payment methods and their settings
  * Do not tick the checkbox Test mode (Tick it only if you're testing your integration with beGateway)
  * Fill in other settings such as 'Notify students', 'Enrol cost', 'Currency', etc.
  * Select 'Yes' in the 'Allow beGateway enrolments' box
  * Click Save changes
  * Navigate to Site administration -> Plugins -> Manage enrol plugins
  * Scroll down to find 'beGateway' and click on the eye icon to enable beGateway as an Enrolment Method

# Add Enrolment Method

To add beGateway as an enrolment method, you must add it to your course.

  * Navigate to Site administration -> Courses -> Manage courses and categories
  * Select the Gear icon on the course you wish to add beGateway as an enrolment method
  * Click Participants from the sidebar
  * Click the Gear icon on the right and select 'Enrolment methods'
  * Select beGateway from the Add method list
  * Fill in the enrolment details as required and click 'Add method'

# Demo credentials

You are free to use the settings to configure the plugin to process
payments with a demo gateway.

  * Shop Id __361__
  * Shop secret key __b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d__
  * Checkout domain __checkout.begateway.com__
  * Gateway domain __demo-gateway.begateway.com__
  * API domain __api.begateway.com__


Use the test data to make a test payment:

  * card number __4200000000000000__
  * card name __John Doe__
  * card expiry date __01/30__ to get a success payment
  * card expiry date __10/30__ to get a failed payment
  * CVC __123__

# Contributing

Issue pull requests or send feature requests or open [a new issue](https://github.com/begateway/moodle-payment-plugin/issues/new)

# Введение

[English version](#Introduction)

Плагин добавляет метод оплаты в Moodle и обеспечивает интеграцию с услугами провайдеров платежей, работающих на платформе beGateway.

# Установка

  * Скачайте плагин beGateway для Moodle напрямую со страницы [релизов Github](https://github.com/begateway/moodle-payment-plugin/releases)
  * Войдите на страницу администратора Moodle
  * Перейдите в Администрирование -> Плагины -> Установка плагинов
  * Нажмите 'Выбрать файл' -> 'Загрузить файл' -> 'Выбрать файл' и выберите zip-файл плагина, затем подтвердите
  * Нажмите 'Показать больше...' и выберите 'Способ записи (enrol)' как тип плагина и подтвердите
  * Нажмите 'Продолжить' после завершения проверки плагина
  * Нажмите 'Продолжить' еще раз на следующей странице
  * Нажмите 'Обновить базу данных Moodle сейчас', затем 'Продолжить' на следующей странице
  * Установка завершена. Перейдите к следующему разделу, чтобы завершить настройку.

# Настройки плагина

  * Перейдите в Администрирование -> Плагины -> Обзор плагинов -> beGateway > Настройки (если настройки не отображаются автоматически)
  * Введите ваш ID магазина
  * Введите ваш Секретный ключ
  * Введите Домен платежной страницы вашего провайдера платежей
  * Введите Домен платёжного шлюза вашего провайдера платежей
  * Введите Домен API вашего провайдера платежей
  * Включите методы оплаты и их настройки
  * Не устанавливайте флажок Тестовый режим (Установите его только при тестировании вашей интеграции с beGateway)
  * Заполните другие настройки, такие как 'Уведомлять студентов', 'Стоимость зачисления', 'Валюта' и т.д.
  * Выберите 'Да' в поле 'Разрешить запись из beGateway'
  * Нажмите 'Сохранить изменения'
  * Перейдите в Администрирование -> Плагины -> Управление плагинами зачисления
  * Прокрутите вниз, чтобы найти 'beGateway', и нажмите на значок глаза, чтобы включить beGateway в качестве метода зачисления.

# Добавление метода зачисления

Для добавления beGateway в качестве метода зачисления вы должны добавить его в свой курс.

  * Перейдите в Администрирование -> Курсы -> Управление курсами и категориями
  * Выберите значок шестеренки на курсе, к которому вы хотите добавить beGateway в качестве метода зачисления
  * Нажмите 'Участники' в боковой панели
  * Нажмите на значок шестеренки справа и выберите 'Способы зачисления на курс'
  * Выберите beGateway из списка Добавить способ
  * Заполните детали зачисления по необходимости и нажмите 'Добавить способ'

# Демо-данные

Вы можете использовать настройки для настройки модуля для обработки платежей с использованием демонстрационного шлюза.

  * ID магазина__361__
  * Секретный ключ __b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d__
  * Домен платежной страницы __checkout.begateway.com__
  * Домен платёжного шлюза __demo-gateway.begateway.com__
  * Домен API __api.begateway.com__


Используйте тестовые данные для проведения тестового платежа:

  * Номер карты __4200000000000000__
  * Имя на карте __John Doe__
  * Дата истечения срока действия карты (успешно) __01/30__
  * Дата истечения срока действия карты (неудачно) __10/30__
  * CVC __123__

# Пожелаяния или вопросы?

Отправляйте запросы на слияние или отправляйте запросы на функции или открывайте [новый вопрос](https://github.com/begateway/moodle-payment-plugin/issues/new).
