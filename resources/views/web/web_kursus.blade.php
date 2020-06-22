<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Bimble | Halaman Kursus</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="all,follow">
    <!-- Price Slider Stylesheets -->
    @include('web.layouts.style')
</head>

<body style="padding-top: 72px;">

    @include('web.layouts.header-simple')


    <div class="container-fluid py-5 px-lg-5">

        <div class="row">
            <div class="col-lg-3 pt-3">
                <form action="{{ route('front.kursus') }}" class="pr-xl-3">
                    <div class="mb-4">
                        <label for="form_search" class="form-label">Keyword</label>
                        <div class="input-label-absolute input-label-absolute-right">
                            <div class="label-absolute"><i class="fa fa-search"></i></div>
                            <input type="search" name="keyword" placeholder="Masukkan Keyword"
                                {{ Request::get('keyword') }} id="form_search" class="form-control pr-4">
                        </div>
                    </div>
                </form>

                <form action="{{ route('front.kursus') }}">
                    <div class="mb-4">
                        <label for="form_category" class="form-label">Kategori</label>
                        <select name="nama_kategori" id="form_category" data-style="btn-selectpicker"
                            data-selected-text-format="count &gt; 1" title="" class="selectpicker form-control">
                            @if ($kategori->count() > 0)
                            @foreach ($kategori as $row)
                            <option value="{{$row->id}}">{{ $row->nama_kategori }}</option>
                            @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="mb-4">
                        <button type="submit" class="btn btn-primary btn-sm"> <i class="fas fa-filter mr-1"></i>Filter
                        </button>

                    </div>
                </form>
            </div>
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center flex-column flex-md-row mb-4">
                    <div class="mr-3">
                        @if (Request::get('nama_kategori') != null)
                        <strong>Kategori: <span class="text-primary">{{ $nama_kategori }}</span> </strong>
                        @else
                        <strong>Semua Kategori</strong>
                        @endif
                    </div>
                    <div>
                        <label for="form_sort" class="form-label mr-2">Sort by</label>
                        <select name="sort" id="form_sort" data-style="btn-selectpicker" title="" class="selectpicker">
                          <option>Termurah</option>
                          <option>Termahal</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <!-- venue item-->
                    @forelse ($kursus as $krs)

                    <div data-marker-id="59c0c8e322f3375db4d89128" class="col-sm-6 col-xl-4 mb-5 hover-animate">
                        <div class="card card-kelas h-100 border-0 shadow">
                            <div class="card-img-top overflow-hidden gradient-overlay">
                                <img src="{{asset('uploads/kursus/'.$krs->gambar_kursus) }}"
                                    alt="{{ $krs->nama_kursus }}" class="img-fluid" /><a
                                    href="{{ route('front.detail', [$krs->slug]) }}" class="tile-link"></a>
                                <div class="card-img-overlay-bottom z-index-20">
                                    <div class="media text-white text-sm align-items-center">
                                        @foreach ($krs->tutor as $sensei)
                                        <img src="{{asset('uploads/tutor/'.$sensei->foto) }}" alt="John"
                                            class="avatar-profile avatar-border-white mr-2" />
                                        <div class="media-body">{{ $sensei->nama_tutor }}</div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="card-body d-flex align-items-center">
                                <div class="w-100">
                                    <h6 class="card-title"><a href="{{ route('front.detail', [$krs->slug]) }}"
                                            class="text-decoration-none text-dark">{{$krs->nama_kursus}}</a></h6>
                                    <div class="d-flex card-subtitle mb-3">
                                        <p class="flex-grow-1 mb-0 text-muted text-sm">
                                            @foreach ($krs->kategori as $item)

                                            {{$item->nama_kategori}}</p>
                                        @endforeach
                                        </p>
                                        <p class="flex-shrink-1 mb-0 card-stars text-xs text-right">
                                            @php
                                            $minat_kursus = $krs->order_detail_count/10;
                                            $rating = round($minat_kursus*2)/2;
                                            @endphp

                                            @for($x = 5; $x > 0; $x--)
                                            @php
                                            if($rating > 0.5) {
                                            echo '<i class="fa fa-star text-warning"></i>';
                                            }
                                            elseif($rating <= 0 ) { echo '<i class="fa fa-star text-gray-300"></i>' ; }
                                                else { echo '<i class="fa fa-star-half text-warning"></i>' ; }
                                                $rating--; @endphp @endfor </p> </div> @if ($krs->diskon_kursus == 0)
                                                <p class="card-text text-muted"><span class="h4 text-primary">
                                                        @currency($krs->biaya_kursus)</span>
                                                    per Bulan</p>
                                                @else
                                                <p class="card-text text-muted">
                                                    <span class="h4 text-primary"> @currency($krs->biaya_kursus -
                                                        ($krs->biaya_kursus * ($krs->diskon_kursus/100)))</span>
                                                    per Bulan
                                                </p>
                                                <p class="card-text ">
                                                    <strike>
                                                        <span
                                                            class="h6 text-danger">@currency($krs->biaya_kursus)</span>
                                                    </strike>
                                                    <strong class="ml-2">Diskon</strong> @currency($krs->diskon_kursus)%
                                                </p>

                                                @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        @endforelse
                    </div>
                </div>

                <!-- Footer-->
                @include('web.layouts.footer')
                <!-- /Footer end-->
                <!-- JavaScript files-->


                @include('web.layouts.script')
</body>

</html>
