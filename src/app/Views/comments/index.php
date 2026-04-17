<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Комментарии</title>
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        .comment-box {
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fff;
            position: relative;
        }
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9em;
            color: #6c757d;
        }
        .comment-text {
            white-space: pre-wrap;
        }
        .delete-btn {
            position: absolute;
            top: 10px;
            right: 15px;
        }
        .sort-links a {
            margin-right: 10px;
        }
        .pagination {
            justify-content: center;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <h1 class="mb-4 text-center">Список комментариев</h1>

    <!-- Sorting controls -->
    <div class="card mb-4">
        <div class="card-body">
            <strong>Сортировка:</strong>
            <span class="sort-links ml-2">
                <a href="?sort_by=id&sort_dir=asc" class="<?= ($sortBy == 'id' && $sortDir == 'asc') ? 'font-weight-bold text-dark' : 'text-primary' ?>">ID &uarr;</a>
                <a href="?sort_by=id&sort_dir=desc" class="<?= ($sortBy == 'id' && $sortDir == 'desc') ? 'font-weight-bold text-dark' : 'text-primary' ?>">ID &darr;</a>
                |
                <a href="?sort_by=date&sort_dir=asc" class="<?= ($sortBy == 'date' && $sortDir == 'asc') ? 'font-weight-bold text-dark' : 'text-primary' ?>">Дата &uarr;</a>
                <a href="?sort_by=date&sort_dir=desc" class="<?= ($sortBy == 'date' && $sortDir == 'desc') ? 'font-weight-bold text-dark' : 'text-primary' ?>">Дата &darr;</a>
            </span>
        </div>
    </div>

    <!-- Comments List -->
    <div id="comments-container">
        <?php if (!empty($comments) && is_array($comments)): ?>
            <?php foreach ($comments as $comment): ?>
                <div class="comment-box shadow-sm" id="comment-<?= esc($comment['id']) ?>">
                    <div class="comment-header">
                        <strong><?= esc($comment['name']) ?></strong>
                        <span style="margin-right:5rem;" ><?= date('d.m.Y', strtotime($comment['date'])) ?></span>
                    </div>
                    <div class="comment-text"><?= esc($comment['text']) ?></div>
                    <button class="btn btn-sm btn-outline-danger delete-btn" data-id="<?= esc($comment['id']) ?>">Удалить</button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted text-center" id="no-comments">Пока нет комментариев.</p>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <div class="mt-4" id="pagination-container">
        <?= $pager->links('default', 'bootstrap_full') ?>
    </div>

    <hr class="my-5">

    <!-- Add Comment Form -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-white">
            <h4 class="mb-0">Оставить комментарий</h4>
        </div>
        <div class="card-body">
            <form id="comment-form">
                <div id="form-messages"></div>
                
                <div class="form-group">
                    <label for="name">Email (будет использован как имя) *</label>
                    <input type="email" class="form-control" id="name" name="name" required placeholder="example@email.com">
                    <div class="invalid-feedback"></div>
                </div>
                
                <div class="form-group">
                    <label for="date">Дата создания *</label>
                    <input type="date" class="form-control" id="date" name="date" required value="<?= date('Y-m-d') ?>">
                    <div class="invalid-feedback"></div>
                </div>

                <div class="form-group">
                    <label for="text">Комментарий *</label>
                    <textarea class="form-control" id="text" name="text" rows="4" required></textarea>
                    <div class="invalid-feedback"></div>
                </div>

                <button type="submit" class="btn btn-primary">Отправить</button>
            </form>
        </div>
    </div>
</div>

<!-- jQuery 3 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    
    // Handle form submission
    $('#comment-form').on('submit', function(e) {
        e.preventDefault();
        
        let form = $(this);
        let submitBtn = form.find('button[type="submit"]');
        let formMessages = $('#form-messages');
        
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').text('');
        formMessages.html('');
        
        submitBtn.prop('disabled', true).text('Отправка...');

        $.ajax({
            url: '<?= site_url('comments') ?>',
            type: 'POST',
            dataType: 'json',
            data: form.serialize(),
            success: function(response) {
                if (response.status === 'success') {
                    // Чтобы соблюдать архитектурную целостность пагинации (3 на страницу),
                    // если мы добавляем комментарий через AJAX, проще всего просто перезагрузить страницу,
                    // либо аккуратно вставить и удалить лишний.
                    // Т.к. требование "без перезагрузки" - желательно, но пагинация "строго по 3" важнее,
                    // мы сделаем компромисс: вставим коммент и если их стало > 3, удалим последний.
                    
                    formMessages.html('<div class="alert alert-success">' + response.message + '</div>');
                    form.trigger("reset");
                    $('#date').val('<?= date('Y-m-d') ?>');
                    
                    let c = response.comment;
                    let dateStr = new Date(c.date).toLocaleDateString('ru-RU');
                    
                    let html = `
                        <div class="comment-box shadow-sm" id="comment-${c.id}">
                            <div class="comment-header">
                                <strong>${c.name}</strong>
                                <span>${dateStr}</span>
                            </div>
                            <div class="comment-text">${c.text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</div>
                            <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${c.id}">Удалить</button>
                        </div>
                    `;
                    
                    $('#no-comments').remove();
                    
                    // Вставляем согласно текущей сортировке
                    if ('<?= $sortDir ?>' === 'desc') {
                        $('#comments-container').prepend(html);
                    } else {
                        $('#comments-container').append(html);
                    }

                    // ОГРАНИЧЕНИЕ: Если на текущей странице стало больше 3 комментариев - удаляем лишний
                    let allComments = $('#comments-container .comment-box');
                    if (allComments.length > 3) {
                        if ('<?= $sortDir ?>' === 'desc') {
                            allComments.last().remove();
                        } else {
                            allComments.last().remove(); // или first, зависит от логики, обычно удаляют тот что ушел на др страницу
                        }
                        
                        // Если пагинации еще нет (было < 3), но теперь должно быть больше - 
                        // тут по-хорошему надо перерисовать пагинатор. 
                        // Для простоты в этом случае лучше все же предложить обновить страницу 
                        // или просто сделать location.reload() если это 4-й комментарий.
                        if ($('#pagination-container').text().trim() === '') {
                             location.reload(); 
                        }
                    }

                    setTimeout(function() {
                        formMessages.html('');
                    }, 3000);
                } else if (response.status === 'error') {
                    if (response.errors) {
                        $.each(response.errors, function(field, message) {
                            let input = $('#' + field);
                            input.addClass('is-invalid');
                            input.siblings('.invalid-feedback').text(message);
                        });
                    } else {
                        formMessages.html('<div class="alert alert-danger">' + response.message + '</div>');
                    }
                }
            },
            error: function() {
                formMessages.html('<div class="alert alert-danger">Ошибка сервера.</div>');
            },
            complete: function() {
                submitBtn.prop('disabled', false).text('Отправить');
            }
        });
    });

    // Handle delete button click
    $('#comments-container').on('click', '.delete-btn', function() {
        let btn = $(this);
        let id = btn.data('id');
        let commentBox = $('#comment-' + id);
        
        if (confirm('Удалить комментарий?')) {
            btn.prop('disabled', true).text('...');
            
            $.ajax({
                url: '<?= site_url('comments/delete/') ?>' + id,
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // После удаления через AJAX на странице может остаться 2 комментария, 
                        // хотя в БД их много. Чтобы "подтянуть" следующий коммент с др. страницы - 
                        // проще обновить страницу.
                        location.reload();
                    } else {
                        alert(response.message);
                        btn.prop('disabled', false).text('Удалить');
                    }
                },
                error: function() {
                    alert('Ошибка сети');
                    btn.prop('disabled', false).text('Удалить');
                }
            });
        }
    });
});
</script>

</body>
</html>
