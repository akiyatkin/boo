{LIST:}
	{:heading}
	<table class="table">
		<tr>
			<td>Имя</td><td>Дата</td><td></td>
		</tr>
		{list::row}
	</table>
	<div style="margin-bottom:50px">
	<a href="/-boo/test">Создать тестовую группу кэша</a>, <a href="/-boo/remove">Удалить весь кэш</a>, <a href="/-boo/refresh">Обновить весь кэш</a>.
</div>
	{row:}
		<tr><td>{name}</td><td>{~date(:j F Y H:i,time)}</td><td><a href="/-boo/refresh/{name}">очистить</a>, <a href="/-boo/remove/{name}">удалить</a></td>
{REFRESH:}
	{:heading}
	<p>Нужно выбрать <a href="/-boo/">группу кэша</a> для очистки</p>
{REMOVE:}
	{:heading}
	<p>Нужно выбрать <a href="/-boo/">группу кэша</a> для очистки</p>
{TEST:}
	{:heading}
	<p>Cоздана группа кэша <b>test</b>.</p>
	<p><a href="/-boo/">Список групп</a></p>
{heading:}
<h1>Группы кэша</h1>