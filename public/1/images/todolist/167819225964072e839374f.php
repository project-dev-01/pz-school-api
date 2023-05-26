@extends('layouts.admin-layout')
@section('content')
<!-- Page Content -->
<div class="content container-fluid">
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                    </ol>
                </div>
                <h4 class="page-title">Employee Master import</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-0">
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <h4 class="nav-link">Employee Master import<h4>
                    </li>
                </ul><br>
                <div class="card-body">
                    <div class="col-12">
                        <div class="col-sm-12 col-md-12">
                            <div class="dt-buttons" style="float:right;"> 
                                <a href="{{asset('uploads/Sample Employee Master.csv')}}" target="_blank"><button class="dt-button buttons-pdf buttons-html5" tabindex="0" aria-controls="employee-table" type="button"><span>Download Sample Csv</span></button></a>
                            </div>
                        </div>
                    </div>
                </div>
                    
                    @if(count($errors) > 0)
                    <div class="alert alert-danger">
                        Upload Validation Error<br><br>
                        <ul>
                            @foreach($errors as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    @if($message = Session::get('success'))
                    <div class="alert alert-success alert-block">
                        <button type="button" class="close" data-dismiss="alert">×</button>
                        <strong>{{ $message }}</strong>
                    </div>
                    @endif
                    <form method="post" enctype="multipart/form-data" action="{{ route('admin.employee_master.import.add') }}">
                        {{ csrf_field() }}
                        <div class="form-group" style="text-align: center;">
                           <div class="card-body" style="margin-left: 17px;">
                               <label style="margin-right:10px;">Select File for Upload</label>
                                        <input type="file" name="file" />
                            </div>  
                            <input type="submit" name="upload" class="btn btn-success" value="Upload">   
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- /Content End -->
    <!-- content start  -->

</div>
<!-- /Page Content -->
@stop