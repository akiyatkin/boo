{ans::}-ans/ans.tpl

{LIST:}
	<div class="boo">
		<style scoped>
			.boo h1 {
				color:black;
			}
			.boo h2 {
				text-transform: none;
			}
			.boo a:active {
				text-decoration: none;
			}
			.boo a:focus {
				text-decoration: none;
			}
			.boo .active {
				font-weight: bold;
			}
			.boo .group {
				color:#00a000;
			}
			.boo .group:hover {
				border-color: #00a000;
			}
			.boo .option {
				color:gray;
			}
			.boo .group:hover {
				border-color: gray;
			}
			.boo .item {
				color:red;
			}
			.boo .item:hover {
				border-color: red;
			}
			.boo .error {
				color:brown;
			}
			.boo .error:hover {
				border-color: brown;
			}
		</style>
		{:heading}
		{msg?msg:ans.msg?:LISTshow}
		<div style="clear:both; margin-top:20px; margin-bottom:50px; font-size:90%; padding-top:20px; border-top:10px solid #999">

			<b>{right::echotitle}</b> <br><a href="/-boo/{path}/refresh">Обновить</a> | <a href="/-boo/{path}/remove">Удалить</a>
			
			<!--<br>
			<a href="/-boo/test">Создать тестовый кэш</a><br>
			<a href="/-boo/form">Анализ страницы</a><br>-->
		</div>
	</div>
{LISTshow:}
	
				
	{:layout-{layout}}
	
	
	{title2:}
		<h2><span style="font-weight: normal; font-size:80%; text-transform: none">{item.src?:Кэш?:Группа}:</span> {item.title}</h2>
	{sources:}
		<a href="/{.}">{.}</a></br>
	{cacheinfo:}
		<div style="margin-bottom:10px">
			<div style="margin-bottom:10px">
				<span class="a" onclick="$('.args').slideToggle()">Аргументы</span> | 
				<span class="a" onclick="$('.result').slideToggle()">Результат</span>
			</div>
			<div style="display:none" class="args">{~print(args)}</div>
			<div style="display:none" class="result">{~print(result)}</div>
		</div>
	{parents:}
		<div>
			{::pathparent}
		</div>
		{pathparent:}
			{~last()&active?:apar?:apar}
			{spana:}
				<span{active?:active}>{title}</span>
			{apar:}
				<a{active?:active} class="{:cls}" href="/-boo/{id}">{title}</a>{~last()|:dash}
			{active:} style="font-weight:bold"
			{dash:} &mdash;
	{childs:}
		<a href="/-boo/{.}" class="item">{items[.]title}</a><br>
	{group:}
		<a href="/-boo/{pathforgroup}{.}" class="group">{groups[.]title}</a><br>
	{rootgroup:}
		<a class="{.=parent?:stractive} option" href="/-boo/{.}.{item.id}">{groups[.]title}</a><br>
	{cls:}{items[id]?:stritem?(groups[id]?:strgroup?:strerror)}
	{stritem:}item
	{stractive:}active
	{strgroup:}group
	{strerror:}error
{TEST:}
	{:heading}
	<p>Cоздана группа кэша <b>{title}</b>.</p>
{heading:}
	<a href="/-boo"><h1>Управление кэшем</h1></a>
{info:}
	Всего <b>{count}</b>, Размер <b>{size}&nbsp;Кб</b>, Последние изменения <b>{~date(:j F Y H:i,time)}</b>
{source:}<a href="/{src}">{src}</a><span style="color:gray">{boo}</span><br>

{layout-root:}
	{:timeinfogroup}
	{:btnrefreshroot}
	{remove?:removelabel}
	{:groups}
	
	
	
{layout-default:}
	{:title}
	{item:timeinfoitem}

	{:btnrefreshitem}

	{path??:groups}

	<div style="margin-bottom:10px">
		{item.src?item:cacheinfo}
	</div>
	{~length(item.paths)>:1?:obrpaths?(~length(item.paths.0)>:1?:obrpaths)}
	{~length(item.childs)?:zavchilds}
	{zavchilds:}
		<div>
			<h2>Зависимости</h2>
			{item.childs::childs}
		</div>
	{obrpaths:}
		<div style="margin-bottom:10px">
			<h2>Обращения</h2>
			{item.paths::parents}
		</div>
{layout-group:}
	{:title}
	{item:timeinfogroup}

	{:btnrefreshgroup}
	{remove?:removelabel}
	{path??:groups}


	<div style="margin-bottom:10px">
		<h2>Родительские группы</h2>
		{item.parentgroups::rootgroup}
		<a class="{parent??:stractive} option" href="/-boo/{item.id}">Корень</a><br>
	</div>	
	{~length(item.childgroups)?:vlgroups}
	
	<!--<div style="margin-bottom:10px">
		Обращений: <b>{~length(item.rel.paths)}</b><br>
		{item.rel.paths::parents}
	</div>-->
	<div style="margin-bottom:10px">
		<h2>Кэш</h2>
		{item.rel.childs::childs}
	</div>

	
{vlgroups:}
	<div style="margin-bottom:10px">
		<h2>Вложенные группы</h2>
		{item.childgroups::group}
	</div>
	
{btnrefreshroot:}
	{refresh?:refreshlabel?:btnrefreshshowroot}
	{btnrefreshshowroot:}
	<div style="margin-bottom:5px">
		<a href="/-boo/{path}/refresh" class="btn btn-danger">Обновить{layout=:root?:strall}</a>
	</div>
	{strall:} весь кэш
{btnrefreshitem:}
	{refresh?:refreshlabel?:btnrefreshshowitem}
	{btnrefreshshowitem:}
	<div style="margin-bottom:5px">
		<a href="/-boo/{path}/refresh" class="btn btn-danger">Обновить <b>{item.title}</b></a>
	</div>
{btnrefreshgroup:}
	{refresh?:refreshlabel?:btnrefreshshowgroup}

	{btnrefreshshowgroup:}
	<div style="margin-bottom:5px">
		<a href="/-boo/{path}/refresh" class="btn btn-danger">Обновить <b>{right::echotitle}</b></a>
	</div>
{timeinfoitem:}
	<div style="margin-bottom:10px">
		Последние изменения: <b>{~date(:H:i j F Y,time)}</b><br>
		Длительность: <b>{timer} сек</b><br>
		Группа: <a href="/-boo/{item.group.id}" class="group">{item.group.title}</a><br>
		Адрес: <a href="/{item.src}">{item.src}</a><br>
	</div>
{timeinfogroup:}
	<div style="margin-bottom:10px">
		Последние изменения: <b>{~date(:H:i j F Y,time)}</b><br>
		Длительность: <b>{timer} сек</b>
	</div>
{groups:}
	{groups::prgroup}
	{prgroup:}
		<a class="group" href="/-boo/{id}">{title}</a><br>
{title:}
		<h2>{right::echop}</h2>
		{echop:}<a href="/-boo/{id}" class="{:cls}" title="{uptitle}">{title}</a>{~last()|:point}
		{pointl:} <a title="Упростить путь" style="text-transform: none" href="/-boo/{item.id}">←</a> 
		{point:} — 
		{echotitle:}{title}{~last()|:point}
{refreshlabel:}<div class="alert alert-success">Выполнено обновление</div>
{removelabel:}<div class="alert alert-danger">Файлы удалены</div>