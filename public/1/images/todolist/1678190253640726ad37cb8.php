@extends('layouts.forum-layout')
@section('content')
<link href="{{ asset('public/css/custom/common.css') }}" rel="stylesheet" type="text/css" />
<!-- <link href="{{ asset('public/css/custom/Responsive.css') }}" rel="stylesheet" type="text/css" /> -->
<link href="{{ asset('public/css/custom/style.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ asset('public/css/custom/opensans-font.css') }}" rel="stylesheet" type="text/css" />

<style>
       .nav-tabs {
        background-color: white;
    }

    .form-default .form-control {
        background: none;
        color: #1b2023;
        font-size: 16px;
        line-height: 25px;
        border: none;
        border-radius: 2px;
        border: 1px solid #929fa7;
        background-color: #F4F7FC;
    }

    .tt-button-icon {
        background-color: none;
        border-radius: 2px;
        border: 2px solid #e2e7ea;
        display: block;
        padding-top: 28px;
        padding-bottom: 19px;
        -webkit-transition: border 0.2s linear;
        transition: border 0.2s linear;
    }

    .tt-button-icon {
        background-color: none;
    }
    .tt-button-icon:hover, .tt-button-icon.active {
    border-color: #E9D528;
}
.ck-placeholder
{
    height:200px;
}
.select2-container .select2-search--inline .select2-search__field {
    box-sizing: border-box;
    border: none;
    font-size: 100%;
    margin-top: 12px;
    margin-left: 10px;
    padding: 0;
    max-width: 100%;
    resize: none;
    height: 21px;
    vertical-align: bottom;
    font-family: sans-serif;
    overflow: hidden;
    word-break: keep-all;
}
.select2-container--default .select2-selection--multiple {
    background-color: #F4F7FC;
}
.pt-editor .pt-title {
    color: #182730;
    font-weight: 600;
    font-size: 16px;
    /* line-height: 26px; */
    margin: 0px;
    padding: 0 0 0 0;
    letter-spacing: 0.01em;
}
.tt-topic-list .tt-list-header.tt-border-bottom
{
border-bottom: 1px solid #E9D528;
}
</style>
<main id="tt-pageContent">
    <div class="container card" style="background:white;">
        <div class="tt-wrapper-inner" id="updatepostForumreset" style="border-bottom: 1px solid #E9D528;">
            <h1 class="tt-title-border">
                <label style="margin-top: 10px;">Create New Topic</label>
            </h1>
            <form class="form-default form-update-topic" id="updatepostForum" method="post" action="{{ route('admin.forum.update-topic') }}" autocomplete="off">
                @csrf
                <input type="hidden" name="id" value="{{$forum_edit['id']}}">
                <div class="form-group">
                    <label for="inputTopicTitle">Topic Title</label>
                    <div class="tt-value-wrapper">
                        <input type="text" name="inputTopicTitle" value="{{$forum_edit['topic_title']}}" class="form-control" id="inputTopicTitle" placeholder="Subject of your topic">
                        <span class="tt-value-input"></span>
                    </div>
                    <div class="tt-note">Describe your topic well, while keeping the subject as short as possible.</div>
                </div>
                <div class="form-group" id="selectedtpy">
                    <input type="hidden" id="topictype" name="topictype" value="{{$forum_edit['types']}}">
                    <label>Topic Type</label>
                    <div class="tt-js-active-btn tt-wrapper-btnicon">
                        <div class="row tt-w410-col-02">
                            <div class="col-4 col-lg-3 col-xl-3">
                                <a href="#" class="tt-button-icon {{$forum_edit['types']=='Discussion'? 'active':''}}">
                                    <span class="tt-icon">
                                        <svg>
                                            <use xlink:href="#icon-discussion"></use>
                                        </svg>
                                    </span>
                                    <span class="tt-text">Discussion</span>
                                </a>
                            </div>
                            <div class="col-4 col-lg-3 col-xl-3">
                                <a href="#" class="tt-button-icon {{$forum_edit['types']=='Question'? 'active':''}}">
                                    <span class="tt-icon">
                                        <svg>
                                            <use xlink:href="#Question"></use>
                                        </svg>
                                    </span>
                                    <span class="tt-text">Question</span>
                                </a>
                            </div>
                            <div class="col-4 col-lg-3 col-xl-3">
                                <a href="#" class="tt-button-icon{{$forum_edit['types']=='Technology'? 'active':''}}" >
                                    <span class="tt-icon">
                                        <svg>
                                            <use xlink:href="#Poll"></use>
                                        </svg>
                                    </span>
                                    <span class="tt-text">Technology</span>
                                </a>
                            </div>
                            <!--  <div class="col-4 col-lg-3 col-xl-2">
                                <a href="#" class="tt-button-icon">
                                    <span class="tt-icon">
                                        <svg>
                                            <use xlink:href="#icon-gallery"></use>
                                        </svg>
                                    </span>
                                    <span class="tt-text">Gallery</span>
                                </a>
                            </div>
                            <div class="col-4 col-lg-3 col-xl-2">
                                <a href="#" class="tt-button-icon">
                                    <span class="tt-icon">
                                        <svg>
                                            <use xlink:href="#Video"></use>
                                        </svg>
                                    </span>
                                    <span class="tt-text">Video</span>
                                </a>
                            </div>-->
                            <div class="col-4 col-lg-3 col-xl-3">
                                <a href="#" class="tt-button-icon {{$forum_edit['types']=='Other'? 'active':''}}">
                                    <span class="tt-icon" >
                                        <svg>
                                            <use xlink:href="#Others"></use>
                                        </svg>
                                    </span>
                                    <span class="tt-text">Other</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputTopicHeader">Topic Header</label>
                    <div class="tt-value-wrapper">
                        <input type="text" name="inputTopicHeader" value="{{$forum_edit['topic_header']}}" class="form-control" id="inputTopicHeader" placeholder="Header of your topic">
                        <span class="tt-value-input"></span>
                    </div>
                    <div class="tt-note">Describe your topic header..</div>
                </div>
                <div class="pt-editor">
                    <h6 class="pt-title">Topic Body</h6>
                    <div class="">
                        <div class="">
                            <ul class="pt-edit-btn">
                                <li><button type="button" class="btn-icon">
                                        <svg class="tt-icon">

                                        </svg>
                                    </button></li>
                                <li><button type="button" class="btn-icon">
                                        <svg class="tt-icon">

                                        </svg>
                                    </button></li>
                                <li><button type="button" class="btn-icon">
                                        <svg class="tt-icon">

                                        </svg>
                                    </button></li>
                                <li><button type="button" class="btn-icon">
                                        <svg class="tt-icon">

                                        </svg>
                                    </button></li>
                                <li><button type="button" class="btn-icon">
                                        <svg class="tt-icon">

                                        </svg>
                                    </button></li>
                                <li><button type="button" class="btn-icon">
                                        <svg class="tt-icon">

                                        </svg>
                                    </button></li>
                                <li class="hr"></li>
                                <li><button type="button" class="btn-icon">
                                        <svg class="tt-icon">

                                        </svg>
                                    </button></li>
                                <li><button type="button" class="btn-icon">
                                        <svg class="tt-icon">

                                        </svg>
                                    </button></li>
                                <li><button type="button" class="btn-icon">
                                        <svg class="tt-icon">

                                        </svg>
                                    </button></li>
                                <li><button type="button" class="btn-icon">
                                        <svg class="tt-icon">

                                        </svg>
                                    </button></li>
                                <li><button type="button" class="btn-icon">
                                        <svg class="tt-icon">

                                        </svg>
                                    </button></li>
                                <li><button type="button" class="btn-icon">
                                        <svg class="tt-icon">

                                        </svg>
                                    </button></li>
                                <li><button type="button" class="btn-icon">
                                        <svg class="tt-icon">

                                        </svg>
                                    </button></li>
                            </ul>
                        </div>
                    </div>
                    <div class="form-group">
                        <textarea name="tpbody" id="tpbody" class="form-control" rows="5" placeholder="Lets get started" style="height:20px"> {{$forum_edit['body_content']}} </textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="category" class="col-3 col-form-label">Category<span class="text-danger">*</span></label>
                                <div class="col-9">
                                    <select id="getCountry" class="form-control" name="category">
                                        <option value="">Select category</option>
                                        @if(!empty($category))
                                        @foreach($category as $c)
                                        <option value="{{$c['id']}}"  {{$c['id'] == $forum_edit['category'] ? "Selected" : "" }}>{{$c['category_names']}}</option>
                                        @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8" style="width: 800px;margin:0 auto;">
                            <div class="form-group">
                                <label for="inputTopic" class="col-3 col-form-label">User</label>
                                
                                <select class="form-control select2-multiple" data-toggle="select2" id="selectedusers" name="tags[]" multiple="multiple" data-placeholder="Select User">
                                    <option value="">Select User</option>
                                    @forelse($usernames as $user)
                                    @php
                                    $selected = "";
                                    @endphp
                                    @foreach(explode(',', $forum_edit['tags']) as $info)
                                    @if($user['id'] == $info)
                                    @php
                                    $selected = "Selected";
                                    @endphp
                                    @endif
                                    @endforeach
                                    <option value="{{$user['id']}}" {{ $selected }}>{{$user['name']}}</option>
                                    @empty
                                    @endforelse
                                </select>
                                <!-- <input type="hidden" id="tags" name="tags"> -->
                                <!-- <input type="text" id="inputTopicTags" placeholder="" autocomplete="off" class="form-control input-lg" />
                                <input type="text" name="inputTopicTags" autocomplete="off" class="form-control" id="inputTopicTags" placeholder="Use comma to separate tags"> -->


                                <!-- <div id="userlist"></div> -->
                                <!-- For defining autocomplete -->
                                <!-- <select class="form-control select2-multiple" data-toggle="select2" multiple="multiple" data-placeholder="Choose ...">
                                    <option value="">Choose Department</option>
                                    @if(!empty($usernames))
                                    @foreach($usernames as $value)
                                    <option value="{{$value['id']}}">{{$value['name']}}</option>
                                    @endforeach
                                    @endif
                                </select>                                -->

                            </div>

                        </div>
                        <br>
                        <span id="grpnames"></span>
                    </div>
                    <div class="row">
                        <div class="col-auto ml-md-auto">
                            <button type="submit" id="search" class="btn btn-secondary btn-width-lg">Update Post</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="tt-topic-list tt-offset-top-30">
            <div class="tt-list-search">
                <div class="tt-title">Suggested Topics</div>
                <!-- tt-search -->
                <div class="tt-search">
                    <form class="search-wrapper">
                        <div class="search-form">
                            <!-- <input type="text" class="tt-search__input" placeholder="Search for topics">
                            <button class="tt-search__btn" type="submit">
                                <svg class="tt-icon">
                                    <use xlink:href="#icon-search"></use>
                                </svg>
                            </button>
                            <button class="tt-search__close">
                                <svg class="tt-icon">
                                    <use xlink:href="#icon-cancel"></use>
                                </svg>
                            </button> -->
                        </div>
                    </form>
                </div>
                <!-- /tt-search -->
            </div>
            
        </div>
    </div>
</main>
@endsection
@section('scripts')
<script>
    var indexPost = "{{ route('admin.forum.page-create-topic') }}";
</script>
<script src="{{ asset('public/js/custom/forum-createpost.js') }}"></script>
<script src="{{ asset('public/js/pages/form-advanced.init.js') }}"></script>
<script>
    function SimpleUploadAdapterPlugin(editor) {
        editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
            // Configure the URL to the upload script in your back-end here!
            return new MyUploadAdapter(loader);
        };
    }

    var myEditor;

    ClassicEditor
        .create(document.querySelector('#tpbody'), {
            extraPlugins: [SimpleUploadAdapterPlugin],

            // ...
        })
        .then(editor => {
            console.log('Editor was initialized', editor);
            myEditor = editor;
        })
        .catch(err => {
            console.error(err.stack);
        });
</script>
<script>
    class MyUploadAdapter {
        // ...
        constructor(loader) {
            // The file loader instance to use during the upload.
            this.loader = loader;
        }
        // Starts the upload process.
        upload() {
            return this.loader.file
                .then(file => new Promise((resolve, reject) => {
                    this._initRequest();
                    this._initListeners(resolve, reject, file);
                    this._sendRequest(file);
                }));
        }

        // Aborts the upload process.
        abort() {
            if (this.xhr) {
                this.xhr.abort();
            }
        }
        // Initializes the XMLHttpRequest object using the URL passed to the constructor.
        _initRequest() {
            const xhr = this.xhr = new XMLHttpRequest();

            // Note that your request may look different. It is up to you and your editor
            // integration to choose the right communication channel. This example uses
            // a POST request with JSON as a data structure but your configuration
            // could be different.
            xhr.open('POST', "{{ route('admin.forum.image.store') }}", true);
            xhr.setRequestHeader('x-csrf-token', '{{csrf_token()}}');
            xhr.responseType = 'json';
        }

        // Initializes XMLHttpRequest listeners.
        _initListeners(resolve, reject, file) {
            const xhr = this.xhr;
            const loader = this.loader;
            const genericErrorText = `Couldn't upload file: ${ file.name }.`;

            xhr.addEventListener('error', () => reject(genericErrorText));
            xhr.addEventListener('abort', () => reject());
            xhr.addEventListener('load', () => {
                const response = xhr.response;

                // This example assumes the XHR server's "response" object will come with
                // an "error" which has its own "message" that can be passed to reject()
                // in the upload promise.
                //
                // Your integration may handle upload errors in a different way so make sure
                // it is done properly. The reject() function must be called when the upload fails.
                if (!response || response.error) {
                    return reject(response && response.error ? response.error.message : genericErrorText);
                }

                // If the upload is successful, resolve the upload promise with an object containing
                // at least the "default" URL, pointing to the image on the server.
                // This URL will be used to display the image in the content. Learn more in the
                // UploadAdapter#upload documentation.
                resolve(response);
            });

            // Upload progress when it is supported. The file loader has the #uploadTotal and #uploaded
            // properties which are used e.g. to display the upload progress bar in the editor
            // user interface.
            if (xhr.upload) {
                xhr.upload.addEventListener('progress', evt => {
                    if (evt.lengthComputable) {
                        loader.uploadTotal = evt.total;
                        loader.uploaded = evt.loaded;
                    }
                });
            }
        }
        // Prepares the data and sends the request.
        _sendRequest(file) {
            // Prepare the form data.
            const data = new FormData();
            data.append('upload', file);
            // Important note: This is the right place to implement security mechanisms
            // like authentication and CSRF protection. For instance, you can use
            // XMLHttpRequest.setRequestHeader() to set the request headers containing
            // the CSRF token generated earlier by your application.
            // Send the request.
            this.xhr.send(data);
        }
    }
</script>
<script>
    document.querySelectorAll('oembed[url]').forEach(element => {
        // Create the <a href="..." class="embedly-card"></a> element that Embedly uses
        // to discover the media.
        const anchor = document.createElement('a');
        anchor.setAttribute('href', element.getAttribute('url'));
        anchor.className = 'embedly-card';
        element.appendChild(anchor);
    });
</script>
@endsection