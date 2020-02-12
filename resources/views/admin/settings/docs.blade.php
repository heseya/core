@extends('admin/layout')

@section('title', 'Dokumentacja API')

@section('buttons')
<a href="/docs/depth.yml" class="top-nav--button">
    <img class="icon" src="/img/icons/download.svg">
</a>
@endsection

@section('content')
<link rel="stylesheet" type="text/css" href="/css/swagger-ui.css" >

<div id="swagger-ui"></div>

<script src="https://unpkg.com/swagger-ui-dist@3/swagger-ui-bundle.js"></script>
<script src="/js/swagger-ui-standalone-preset.js"></script>
<script>
window.onload = function() {
    // Begin Swagger UI call region
    const ui = SwaggerUIBundle({
        url: "/docs/depth.yml",
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
            SwaggerUIBundle.presets.apis,
        ],
    })
    // End Swagger UI call region

    window.ui = ui
}
</script>
@endsection
