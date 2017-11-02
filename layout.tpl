{LIST:}
	{:heading}
	<table class="table">
		<tr>
			<td>Имя</td><td>Дата</td><td></td>
		</tr>
		{list::row}
	</table>
	<a href="/-boo/test">Создать тестовую группу кэша</a>
	{row:}
		<tr><td>{name}</td><td>{~date(:j F Y H:i,time)}</td><td><a href="clear/{name}">Очистить</a></td>
{CLEAR:}
	{:heading}
	<p>Нужно выбрать <a href="/-boo/">группу кэша</a> для очистки</p>
{TEST:}
	{:heading}
	<p>Cоздана группа кэша <b>test</b>.</p>
	<p><a href="/-boo/">Список групп</a></p>

{CLEARED:}
	{:heading}
	<p>{result?:istrue?:isfalse}</p>
	<p><a href="/-boo/">Список групп</a></p>	
	{istrue:}Группа кэша <b>{name}</b> удалена!
	{isfalse:}Произошла ошибка. Группа кэша <b>{name}</b> не удалена!</p>
{heading:}<h1>Кэш-группы</h1>