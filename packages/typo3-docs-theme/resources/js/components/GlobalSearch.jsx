import React, { useState } from 'react';
import SearchModal from './SearchModal';

const GlobalSearch = () => {
    const [isModalOpen, setIsModalOpen] = useState(false);

    const handleInputClick = () => {
        setIsModalOpen(true);
    };

    return (
        <>
            <div class="sr-only"><label for="globalsearchinput">TYPO3 documentation...</label></div>
            <div class="input-group mb-3 mt-sm-3">
                <select class="form-select search__scope" id="searchscope" name="scope">
                    <option value="">Search all</option>
                </select>
                <input onClick={handleInputClick} autocomplete="off" class="form-control shadow-none" id="globalsearchinput" name="q" placeholder="TYPO3 documentation..." type="text" value="" />
                <button class="btn btn-light search__submit" type="submit"><i class="fa fa-search"></i>&nbsp;<span class="d-none d-md-inline">Search</span></button>
            </div>
            <SearchModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
            />
        </>
    );
};

export default GlobalSearch;