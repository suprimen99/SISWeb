    <div class="modal fade" id="add-galeri-modal" tabindex="-1" role="dialog" aria-labelledby="modal-add-galeri" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                <h5 class="modal-title" id="modalCenterTitle">Tambah data</h5>
                <button type="button" class="btn-close close-modal-tambah" data-bs-dismiss="modal" aria-label="Close" ></button>
                </div>
                <form id="form-add-galeri" enctype="multipart/form-data">
                <div class="modal-body">

                    <div class="row">
                        <div class="col mb-3">
                            <label class="form-label">Nama Kegiatan<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_kegiatan" id="nama_kegiatan"/>
                            <small class="text-danger mt-2 error-message" id="nama_kegiatan-error"></small>
                        </div>
                    </div>

                    <div class="row">
                    <div class="col mb-3">
                        <label class="form-label">Title<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" id="title"/>
                        <small class="text-danger mt-2 error-message" id="title-error"></small>
                    </div>
                    </div>

                    <div class="row">
                    <div class="col mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="deskripsi" id="deskripsi"></textarea>
                        <small class="text-danger mt-2 error-message" id="deskripsi-error"></small>
                    </div>
                    </div>

                    <div class="row">
                    <div class="col mb-3">
                        <label class="form-label">Gambar<span class="text-danger">*</span></label>
                        <div class="input-group">
                        <input type="file" class="form-control" name="gambar" id="gambar" />
                        <label class="input-group-text">Upload</label>
                        </div>
                        <small class="text-danger mt-2 error-message" id="gambar-error"></small>
                    </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
                </form>
            </div>
            </div>
        </div>
    </div>

    @section('scripts')
        <script>
            $('body').on('click', '#add-galeri', function() {
                // open modal
                $('#loading-overlay').show();
                setTimeout(() => {
                    $('#loading-overlay').hide();
                    $('#add-galeri-modal').modal('show');
                }, 800);
            });


            // simpan data
            $(document).ready(function() {
                $('#form-add-galeri').on('submit', function(e){
                    e.preventDefault();

                    $('#title').on('input', function(){
                        const inputVal = $(this).val();
                        const maxLength = 255;
                        if(inputVal !== '' || inputVal <= maxLength){
                            $('#title-error').text('');
                        }
                    });

                $('#deskripsi').on('input', function(){
                    const inputVal = $(this).val();
                    const maxLength = 255;
                    if (inputVal !=='' || inputVal.length<= maxLength){
                    $('#deskripsi-error').text('');
                    }
                });

                $('#gambar').on('change', function(){
                if(inputVal !== ''){
                    $('#gambar-error').text('')
                }
                });

                var formData = new FormData(this);

                $.ajaxSetup({
                headers : {
                    'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content')
                }
                });

                $('#loading-overlay').show();
                setTimeout(() => {
                $.ajax({
                    type: 'POST',
                    url: '{{route('galeri-content.store')}}',
                    data: formData,
                    dataType: 'json',
                    processData: false,
                    contentType: false,
                    success: function(response){
                    if(response.status == 200){
                        // tutup modal add banner dan kosongkan form
                        $('#loading-overlay').hide();
                        $('#add-galeri-modal').modal('hide');
                        $('#form-add-galeri')[0].reset();

                        Swal.fire({
                        customClass:{
                            container: 'my-swal',
                        },
                        title: `Created!`,
                        text: `${response.message}`,
                        icon: `success`
                        });

                        //    reload halaman
                        setTimeout(function(){
                            location.reload();
                        },800)
                }
                },
                error: function(response)
                {
                    if(response.status == 400){
                    let errors = response.responseJSON.message;
                    for (let key in errors) {
                      let errorMessage = errors[key].join(', ');
                      $('#' + key + '-error').text(errorMessage);
                    }
                    }
                    if(response.status == 500){
                        var res = response;

                        Swal.fire({
                            customClass :{
                                container : 'my-swal',
                            },
                            title: `${res.statusText}`,
                            text: `${res.responseJson.message}`,
                            icon: 'error'
                        });
                    }
                    $('#loading-overlay').hide();
                },
                })
            }, 900);

            });
        });

            // untuk menghapus pesan error ketika modal tertutup dan menghapus form
            $(document).ready(function(){
            // menambahkan event listener pada tombol close
            $('.close-modal-tambah').on('click', function (e){
                $('.error-message').text('');
                $('#form-add-galeri')[0].reset();
            });

            // menambahkan event listener pada modal
            $('#add-galeri-modal').on('hidden.bs.modal', function (e){
                $('.error-message').text(''); //menghapus pesan error
                $('#form-add-galeri')[0].reset(); //mereset form
            });

            });
        </script>
    @endsection
