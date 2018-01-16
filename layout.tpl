{ans::}-ans/ans.tpl
{LIST:}
	{:heading}
	{msg:ans.msg}
	<div class="boo">
		<!--<small>{parent::pathparent}</small>-->
		<div style="float:right">
			Дата: <b>{~date(:H:i j F Y,item.time)}</b><br>
			Длительность: <b>{item.timer}</b> сек
		</div>
		{~length(item.parents)?:title}
		
		
			
			
		<div style="margin:5px 0">
			<a href="/-boo/{path}/refresh/deep" class="btn btn-success">Обновить</a>
		</div>
		<div style="margin-bottom:10px">
			{~length(item.parents)?:notroot}
			{item.childs::childs}
		</div>
		{~length(item.parents)?:notroot2}
			
		
		
	
		<div style="margin-top:20px; margin-bottom:50px; font-size:90%; padding-top:20px; border-top:10px solid #999">

			Обновить &mdash; 
			<a href="/-boo/{path}/refresh">без зависимостей</a>,
			<a href="/-boo/{path}/refresh/deep">с зависимостями выбранного способа обращения</a>,
			<a href="/-boo/{path}/refresh/wide">со всеми зависимостями</a>.<br>

			Удалить &mdash;
			<a href="/-boo/{path}/remove">без зависимостей</a>,
			<a href="/-boo/{path}/remove/deep">с зависимостями выбранного способа обращения</a>,
			<a href="/-boo/{path}/remove/wide">со всеми зависимостями</a>.<br>
			
			<!--<br>
			<a href="/-boo/test">Создать тестовый кэш</a><br>
			<a href="/-boo/form">Анализ страницы</a><br>-->
		</div>
	</div>
	{title:}
		<h2><span style="font-weight: normal; font-size:80%; text-transform: none">{item.src?:Кэш?:Группа}:</span> {item.title}</h2>
	{notroot2:}
		<div style="margin-bottom:10px">
			<div>Зависимости при других способах: <b>{~length(item.dependencies)}</b></div>
			{item.dependencies::childs}
		</div>
		{item.src?:moregroup}
	{notroot:}
		
		{item.src?item:cacheinfo}
		
		<div style="margin-bottom:10px">
			Способов обращений: <b>{~length(item.parents)|:один}</b><br>
			{item.parents::parents}
		</div>
		<div>Зависимости выбранного способа: <b>{~length(item.childs)}</b></div>
	{moregroup:}
		<div>Адрес: <small><a href="/{item.src}">{item.src}</a></small></div>
		
	{sources:}
		<a href="/{.}">{.}</a></br>
	{cacheinfo:}
		<div style="margin-bottom:10px">
			<div>
				<span class="a" onclick="$('.args').slideToggle('slow')">Аргументы: {~length(args)}</span> | <span class="a" onclick="$('.result').slideToggle('slow')">Результат</span>
			</div>		
			<div style="display:none" class="args">{~print(args)}</div>
			<div style="display:none" class="result">{~print(result)}</div>
		</div>


	{printpathparents:}
		<hr>
		<b>Способы обращений</b><br>
		<small>{item.parents::parents}</small>
		<hr>
	{parents:}
		<div>
			{::pathparent}
		</div>
		{pathparent:}
			{~last()&active?:apar?:apar}
			{spana:}
				<span{active?:active}>{title}</span>
			{apar:}
				<a{active?:active} href="/-boo/{path}">{title}</a>{~last()|:dash}
			{active:} style="font-weight:bold"
			{dash:} &mdash;
	{childs:}
		<a href="/-boo/{path}">{title}</a><br>
{TEST:}
	{:heading}
	<p>Cоздана группа кэша <b>{title}</b>.</p>
{heading:}
	<h1>Управление кэшем</h1>
{info:}
	Всего <b>{count}</b>, Размер <b>{size}&nbsp;Кб</b>, Последние изменения <b>{~date(:j F Y H:i,time)}</b>
{source:}<a href="/{src}">{src}</a><span style="color:gray">{boo}</span><br>