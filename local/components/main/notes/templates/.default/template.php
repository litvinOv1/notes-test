<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
?>
<div id="notes-app"></div>

<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>

<script>
const { createApp, ref, onMounted, computed } = Vue;

createApp({
    setup() {
        const notes = ref([])
        const form = ref({ title: '', content: '' })
        const editing = ref(null)
        const message = ref('')
        
        const api = '/local/api/notes.php'
        
        // Вычисляемые свойства для v-model
        const title = computed({
            get() {
                return editing.value ? editing.value.title : form.value.title
            },
            set(value) {
                if (editing.value) {
                    editing.value.title = value
                } else {
                    form.value.title = value
                }
            }
        })
        
        const content = computed({
            get() {
                return editing.value ? editing.value.content : form.value.content
            },
            set(value) {
                if (editing.value) {
                    editing.value.content = value
                } else {
                    form.value.content = value
                }
            }
        })
        
        // Функция для форматирования даты
        const formatDate = (dateString) => {
            if (!dateString) return ''
            
            try {
                const date = new Date(dateString)
                
                // Проверяем валидность даты
                if (isNaN(date.getTime())) {
                    return dateString // возвращаем оригинальную строку если дата невалидна
                }
                
                // Форматируем дату
                return date.toLocaleDateString('ru-RU', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                })
            } catch (e) {
                return dateString
            }
        }
        
        // Загрузить заметки
        const loadNotes = async () => {
            try {
                const res = await axios.get(api)
                notes.value = res.data.data || []
            } catch (e) {
                message.value = 'Ошибка загрузки'
            }
        }
        
        // Создать
        const create = async () => {
            if (!form.value.title.trim()) {
                message.value = 'Введите заголовок'
                return
            }
            
            try {
                await axios.post(api, form.value)
                form.value = { title: '', content: '' }
                loadNotes()
                message.value = 'Создано'
            } catch (e) {
                message.value = 'Ошибка создания'
            }
        }
        
        // Редактировать
        const edit = (note) => {
            editing.value = { ...note }
        }
        
        // Обновить
        const update = async () => {
            try {
                await axios.put(api + '?id=' + editing.value.id, editing.value)
                editing.value = null
                loadNotes()
                message.value = 'Обновлено'
            } catch (e) {
                message.value = 'Ошибка обновления'
            }
        }
        
        // Удалить
        const remove = async (id) => {
            if (!confirm('Удалить?')) return
            try {
                await axios.delete(api + '?id=' + id)
                loadNotes()
                message.value = 'Удалено'
            } catch (e) {
                message.value = 'Ошибка удаления'
            }
        }
        
        // Отмена редактирования
        const cancelEdit = () => {
            editing.value = null
        }
        
        onMounted(loadNotes)
        
        return {
            notes, form, editing, message, title, content, formatDate,
            create, edit, update, remove, cancelEdit, loadNotes
        }
    },
    template: `
    <div style="max-width: 600px; margin: 20px auto; font-family: Arial;">        
        <div v-if="message" style="padding: 10px; background: #f0f0f0; margin: 10px 0;">
            {{ message }}
        </div>
        
        <!-- Форма -->
        <div style="background: #f9f9f9; padding: 20px; margin-bottom: 20px;">
            <h3>{{ editing ? 'Редактировать' : 'Новая заметка' }}</h3>
            <input v-model="title" 
                   placeholder="Заголовок" 
                   style="width: 100%; padding: 8px; margin-bottom: 10px;">
            <textarea v-model="content" 
                      placeholder="Текст" 
                      style="width: 100%; height: 100px; padding: 8px; margin-bottom: 10px;"></textarea>
            <div>
                <button @click="editing ? update() : create()" 
                        style="padding: 8px 16px; background: #007bff; color: white; border: none; margin-right: 10px;">
                    {{ editing ? 'Сохранить' : 'Создать' }}
                </button>
                <button v-if="editing" @click="cancelEdit" 
                        style="padding: 8px 16px; background: #6c757d; color: white; border: none;">
                    Отмена
                </button>
            </div>
        </div>
        
        <!-- Список -->
        <div v-if="notes.length">
            <div v-for="note in notes" :key="note.id" 
                 style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px;">
                <h4 style="margin: 0 0 10px 0;">{{ note.title }}</h4>
                <p style="white-space: pre-wrap; margin: 0 0 10px 0;">{{ note.content }}</p>
                <small style="color: #666;">
                    Изменено: {{ formatDate(note.updated_at) }}
                </small>
                <div style="margin-top: 10px;">
                    <button @click="edit(note)" 
                            style="padding: 5px 10px; background: #28a745; color: white; border: none; margin-right: 5px;">
                        Редактировать
                    </button>
                    <button @click="remove(note.id)" 
                            style="padding: 5px 10px; background: #dc3545; color: white; border: none;">
                        Удалить
                    </button>
                </div>
            </div>
        </div>
        <div v-else style="text-align: center; color: #666; padding: 40px;">
            Нет заметок
        </div>
    </div>
    `
}).mount('#notes-app')
</script>