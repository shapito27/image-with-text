На входе:

 -заголовок поста
 -картинка-фон
 
На выходе:

 -картинка с нанесенным текстом(текст должен быть выровнен)
 
 Алгоритм:
 1. Узнаем ширину высоту картинки
 2. По шрифту вычисляев высоту текста.
 3. Вычисляем длинну текста.
 4. Сравниваем длинну текста и ширину картинки, Учитываем минимальный оступ от краев картинки(счтить в % от ширины картинки)
 5. Если текст слишком длинный, то делим текст по пробелам и получаем длинну каждой части
 6. Выбираем где ставим перенос с учетом того что обе части подходят по ширине
 7. Если всё еще не помещается, то повторяем п.6
 8. Если теперь не одна строка текста, то надо расчитать высоту текста и отцентровать
 9.