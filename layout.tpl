{ans::}-ans/ans.tpl
{LIST:}
	{:heading}
	{msg:ans.msg}
	<div class="boo">
		<style>
			.boo tr .magic {
				visibility:hidden;
			}
			.boo tr:hover .magic {
				visibility:visible;
			}
		</style>
		<div style="font-size:80%; float:right">{:info}</div>

		<table class="table table-striped">
			<tr>
				<th>Имя</th><th>Время</th><th>Дата</th><th></th>
			</tr>
			{list::row}
		</table>
		<div style="margin-bottom:50px">
		<a href="/-boo/test">Создать тестовую группу кэша</a>, <a href="/-boo/remove">Удалить весь кэш</a>, <a href="/-boo/refresh">Обновить весь кэш</a>.
	</div>
</div>
	{row:}
		<tr>
			<td>{name}
			<div style="font-size:80%;">{:info}</div>
			<div class="magic">
				<div style="font-size:80%">{sources::source}</div>
			</div>
		</td><td>{~date(:H:i,time)}</td><td>{~date(:d.m.Y,time)}</td><td>
			<div class="magic"><a href="/-boo/refresh/{name}">обновить</a>, <a href="/-boo/remove/{name}">удалить</a></div>
		</td>
{TEST:}
	{:heading}
	<p>Cоздана группа кэша <b>test</b>.</p>
	<p><a href="/-boo/">Список групп</a></p>
{heading:}
<h1>Группы кэша</h1>
{info:}
	Всего <b>{count}</b>, Размер <b>{size}&nbsp;Кб</b>, Последние изменения <b>{~date(:j F Y H:i,time)}</b>
{source:}<a href="/{src}">{src}</a><span style="color:gray">{boo}</span><br>